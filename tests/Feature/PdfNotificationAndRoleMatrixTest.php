<?php

namespace Tests\Feature;

use App\Mail\ApplicationCreatedMail;
use App\Mail\SpecialApprovalRequestedMail;
use App\Models\AdminUser;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationItem;
use App\Models\ContractPlan;
use App\Models\CustomerContractPlan;
use App\Models\CustomerUser;
use App\Models\DeliveryLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\Maker;
use App\Models\MedicalInstitution;
use App\Models\Notification;
use App\Models\UniformProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Closes review gaps: real PDF issue/download/reissue, notification+mail,
 * stock-shortage reason required, and sales/accounting/contract_plan_manager denials.
 */
class PdfNotificationAndRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_all_creates_four_pdf_types_and_download_works(): void
    {
        Storage::fake('local');
        $ctx = $this->seedContext();
        $app = $this->makeInProgressApplication($ctx);

        $this->actingAs($ctx['ops'])
            ->post(route('admin.applications.documents.issue', $app))
            ->assertRedirect()
            ->assertSessionHas('status');

        $docs = ApplicationDocument::where('application_id', $app->id)
            ->where('is_latest', true)
            ->get();
        $this->assertCount(4, $docs);
        $this->assertEqualsCanonicalizing(
            ApplicationDocument::TYPES,
            $docs->pluck('form_type')->all()
        );

        foreach ($docs as $doc) {
            Storage::disk('local')->assertExists($doc->file_path);
            $this->assertGreaterThan(500, Storage::disk('local')->size($doc->file_path));

            $this->actingAs($ctx['ops'])
                ->get(route('admin.application-documents.download', $doc))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            $this->actingAs($ctx['ops'])
                ->get(route('admin.application-documents.preview', $doc))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_reissue_bumps_version_and_marks_previous_not_latest(): void
    {
        Storage::fake('local');
        $ctx = $this->seedContext();
        $app = $this->makeInProgressApplication($ctx);

        $this->actingAs($ctx['ops'])
            ->post(route('admin.applications.documents.issue', $app))
            ->assertRedirect();

        $first = ApplicationDocument::where('application_id', $app->id)
            ->where('form_type', ApplicationDocument::TYPE_ORDER_SLIP)
            ->where('is_latest', true)
            ->first();
        $this->assertNotNull($first);
        $this->assertSame(1, (int) $first->version);

        $this->actingAs($ctx['ops'])
            ->post(route('admin.applications.documents.reissue', [
                'application' => $app,
                'formType' => ApplicationDocument::TYPE_ORDER_SLIP,
            ]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $first->refresh();
        $this->assertFalse($first->is_latest);

        $second = ApplicationDocument::where('application_id', $app->id)
            ->where('form_type', ApplicationDocument::TYPE_ORDER_SLIP)
            ->where('is_latest', true)
            ->first();
        $this->assertNotNull($second);
        $this->assertSame(2, (int) $second->version);
        Storage::disk('local')->assertExists($second->file_path);
    }

    public function test_sales_cannot_issue_or_download_pdfs(): void
    {
        Storage::fake('local');
        $ctx = $this->seedContext();
        $app = $this->makeInProgressApplication($ctx);

        // Seed docs as ops first
        $this->actingAs($ctx['ops'])
            ->post(route('admin.applications.documents.issue', $app));
        $doc = ApplicationDocument::where('application_id', $app->id)->first();

        $this->actingAs($ctx['sales'])
            ->post(route('admin.applications.documents.issue', $app))
            ->assertForbidden();

        $this->actingAs($ctx['sales'])
            ->get(route('admin.application-documents.download', $doc))
            ->assertForbidden();
    }

    public function test_application_create_notifies_ops_and_sends_mail(): void
    {
        Mail::fake();
        $ctx = $this->seedContext(stock: 20);

        $this->actingAs($ctx['customer'])
            ->post(route('applications.store'), $this->applyPayload($ctx, qty: 1))
            ->assertRedirect()
            ->assertSessionHas('success');

        $app = Application::where('employee_id', $ctx['employee']->id)->first();
        $this->assertNotNull($app);
        $this->assertSame('pending', $app->status);

        $this->assertTrue(
            Notification::query()
                ->where('user_id', $ctx['ops']->id)
                ->where('type', 'application_created')
                ->where('data->application_id', $app->id)
                ->exists()
        );

        Mail::assertQueued(ApplicationCreatedMail::class, function ($mail) use ($ctx) {
            return $mail->hasTo($ctx['ops']->email)
                || $mail->hasTo($ctx['institution']->notification_email);
        });
    }

    public function test_stock_shortage_without_reason_is_rejected(): void
    {
        Mail::fake();
        $ctx = $this->seedContext(stock: 0);

        $this->actingAs($ctx['customer'])
            ->from(route('applications.create'))
            ->post(route('applications.store'), $this->applyPayload($ctx, qty: 1))
            ->assertRedirect(route('applications.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('applications', [
            'employee_id' => $ctx['employee']->id,
            'type' => 'new_loan',
        ]);
        Mail::assertNothingOutgoing();
    }

    public function test_stock_shortage_with_reason_notifies_special_approval(): void
    {
        Mail::fake();
        $ctx = $this->seedContext(stock: 0);

        $payload = $this->applyPayload($ctx, qty: 1);
        $payload['special_approval_reason'] = 'Urgent backorder OK';

        $this->actingAs($ctx['customer'])
            ->post(route('applications.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $app = Application::where('employee_id', $ctx['employee']->id)->first();
        $this->assertSame('waiting_special_approval', $app->status);

        $this->assertTrue(
            Notification::query()
                ->where('user_id', $ctx['ops']->id)
                ->where('type', 'special_approval_requested')
                ->exists()
        );
        Mail::assertQueued(SpecialApprovalRequestedMail::class);
    }

    public function test_sales_role_deny_matrix(): void
    {
        $ctx = $this->seedContext();

        $this->actingAs($ctx['sales'])
            ->get(route('admin.contract-plans.index'))
            ->assertOk();

        $this->actingAs($ctx['sales'])
            ->get(route('admin.billing.index'))
            ->assertOk();

        $this->actingAs($ctx['sales'])
            ->get(route('admin.billing.create'))
            ->assertForbidden();

        $this->actingAs($ctx['sales'])
            ->post(route('admin.billing.generate'), [
                'medical_institution_id' => $ctx['institution']->id,
                'target_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($ctx['sales'])
            ->get(route('admin.applications.index'))
            ->assertForbidden();

        $this->actingAs($ctx['sales'])
            ->get(route('admin.contract-plans.create'))
            ->assertForbidden();
    }

    public function test_accounting_role_deny_matrix(): void
    {
        $ctx = $this->seedContext();

        $this->actingAs($ctx['accounting'])
            ->get(route('admin.billing.create'))
            ->assertOk();

        $this->actingAs($ctx['accounting'])
            ->get(route('admin.applications.index'))
            ->assertForbidden();

        $this->actingAs($ctx['accounting'])
            ->get(route('admin.contract-plans.create'))
            ->assertForbidden();

        $this->actingAs($ctx['accounting'])
            ->get(route('admin.makers.create'))
            ->assertForbidden();
    }

    public function test_contract_plan_manager_role_deny_matrix(): void
    {
        $ctx = $this->seedContext();

        $this->actingAs($ctx['planManager'])
            ->get(route('admin.contract-plans.create'))
            ->assertOk();

        $this->actingAs($ctx['planManager'])
            ->get(route('admin.customer-contract-plans.create'))
            ->assertOk();

        $this->actingAs($ctx['planManager'])
            ->get(route('admin.applications.index'))
            ->assertForbidden();

        $this->actingAs($ctx['planManager'])
            ->get(route('admin.billing.create'))
            ->assertForbidden();

        $this->actingAs($ctx['planManager'])
            ->post(route('admin.billing.generate'), [
                'medical_institution_id' => $ctx['institution']->id,
                'target_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function seedContext(int $stock = 10): array
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-GAP',
            'name' => 'Gap Hospital',
            'notification_email' => 'gap-hospital@example.com',
            'billing_closing_day' => 1,
        ]);
        $department = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'D-G',
            'name' => 'Dept',
        ]);
        $delivery = DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => 'Dock',
        ]);
        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'primary_department_id' => $department->id,
            'staff_no' => 'E-GAP',
            'name' => 'Worker',
            'status' => 'employed',
            'join_date' => now()->subMonths(2)->toDateString(),
        ]);

        $maker = Maker::create(['code' => 'MK-G', 'name' => 'Maker']);
        $product = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-GAP',
            'name' => 'Scrub',
            'category' => 'top',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => $stock,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-GAP',
            'name' => 'Plan Gap',
            'rentable_points' => 10,
            'rentable_points_top' => 10,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);
        $plan->uniformProducts()->attach([$product->id => ['slot_category' => 'top']]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 5,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        EmployeeContractPlan::create([
            'employee_id' => $employee->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => now()->subMonth()->toDateString(),
        ]);

        $customer = $this->makeUser('customer', 'gap-c@example.com');
        CustomerUser::create([
            'user_id' => $customer->id,
            'medical_institution_id' => $institution->id,
            'role' => 'institution_admin',
        ]);

        $ops = $this->makeAdmin('operation', 'gap-ops@example.com');
        $sales = $this->makeAdmin('sales', 'gap-sales@example.com');
        $accounting = $this->makeAdmin('accounting', 'gap-acct@example.com');
        $planManager = $this->makeAdmin('contract_plan_manager', 'gap-cpm@example.com');

        return compact(
            'institution',
            'department',
            'delivery',
            'employee',
            'product',
            'plan',
            'customer',
            'ops',
            'sales',
            'accounting',
            'planManager'
        );
    }

    private function makeUser(string $type, string $email): User
    {
        return User::factory()->create([
            'type' => $type,
            'email' => $email,
        ]);
    }

    private function makeAdmin(string $role, string $email): User
    {
        $user = $this->makeUser('admin', $email);
        AdminUser::create([
            'user_id' => $user->id,
            'role' => $role,
            'receives_app_notifications' => true,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function makeInProgressApplication(array $ctx): Application
    {
        $app = Application::create([
            'medical_institution_id' => $ctx['institution']->id,
            'department_id' => $ctx['department']->id,
            'employee_id' => $ctx['employee']->id,
            'type' => 'new_loan',
            'status' => Application::STATUS_IN_PROGRESS,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'delivery_location_id' => $ctx['delivery']->id,
            'requested_by_user_id' => $ctx['customer']->id,
            'accepted_at' => now(),
        ]);
        ApplicationItem::create([
            'application_id' => $app->id,
            'uniform_product_id' => $ctx['product']->id,
            'quantity' => 1,
            'action' => 'loan',
        ]);

        return $app;
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function applyPayload(array $ctx, int $qty = 1): array
    {
        return [
            'application_mode' => 'individual',
            'employee_id' => $ctx['employee']->id,
            'department_id' => $ctx['department']->id,
            'type' => 'new_loan',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'delivery_location_id' => $ctx['delivery']->id,
            'items' => [
                ['uniform_product_id' => $ctx['product']->id, 'quantity' => $qty],
            ],
        ];
    }
}
