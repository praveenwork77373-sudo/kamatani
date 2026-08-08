<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Application;
use App\Models\ApplicationItem;
use App\Models\ContractPlan;
use App\Models\CustomerContractPlan;
use App\Models\CustomerUser;
use App\Models\DeliveryLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\EmployeeLoan;
use App\Models\Maker;
use App\Models\MedicalInstitution;
use App\Models\UniformProduct;
use App\Models\User;
use App\Services\ApplicationPdfService;
use App\Services\BillingGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end scenarios: setup → apply → accept → bill, plus client-rule edge cases.
 */
class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ApplicationPdfService::class, function ($mock) {
            $mock->shouldReceive('issueAll')->andReturn(collect());
        });
    }

    public function test_happy_path_apply_accept_bill(): void
    {
        $ctx = $this->seedFullContext(rentableTop: 5, stock: 10);

        $this->actingAs($ctx['customer'])
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $ctx['employee']->id,
                'department_id' => $ctx['department']->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $ctx['delivery']->id,
                'items' => [
                    ['uniform_product_id' => $ctx['top']->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect(route('applications.create'))
            ->assertSessionHas('success');

        $app = Application::where('employee_id', $ctx['employee']->id)->first();
        $this->assertNotNull($app);
        $this->assertSame('pending', $app->status);

        $this->actingAs($ctx['admin'])
            ->post(route('admin.applications.approve', $app))
            ->assertRedirect();

        $app->refresh();
        $this->assertSame(Application::STATUS_IN_PROGRESS, $app->status);

        $loan = EmployeeLoan::where('employee_id', $ctx['employee']->id)
            ->where('uniform_product_id', $ctx['top']->id)
            ->where('status', 'in_use')
            ->first();
        $this->assertNotNull($loan);
        $this->assertSame(2, (int) $loan->quantity);

        $ctx['top']->refresh();
        $this->assertSame(8, (int) $ctx['top']->stock_quantity);

        $asOf = now()->day((int) $ctx['institution']->billing_closing_day)->startOfDay();
        $result = app(BillingGenerationService::class)->generateDueClosingDay($asOf);
        $this->assertSame(1, $result['generated']);
    }

    public function test_new_loan_cannot_forge_return_items_to_bypass_cap(): void
    {
        $ctx = $this->seedFullContext(rentableTop: 2, stock: 20);

        EmployeeLoan::create([
            'employee_id' => $ctx['employee']->id,
            'uniform_product_id' => $ctx['top']->id,
            'quantity' => 2,
            'status' => 'in_use',
            'loan_start_date' => now()->subWeek()->toDateString(),
        ]);

        $this->actingAs($ctx['customer'])
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $ctx['employee']->id,
                'department_id' => $ctx['department']->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $ctx['delivery']->id,
                'items' => [
                    ['uniform_product_id' => $ctx['top']->id, 'quantity' => 1],
                ],
                // Forged returns to fake freed capacity
                'return_items' => [
                    ['uniform_product_id' => $ctx['top']->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect(route('applications.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('applications', [
            'employee_id' => $ctx['employee']->id,
            'type' => 'new_loan',
        ]);
    }

    public function test_second_pending_app_blocked_when_first_would_fill_cap(): void
    {
        $ctx = $this->seedFullContext(rentableTop: 2, stock: 20);

        $this->actingAs($ctx['customer'])
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $ctx['employee']->id,
                'department_id' => $ctx['department']->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $ctx['delivery']->id,
                'items' => [
                    ['uniform_product_id' => $ctx['top']->id, 'quantity' => 2],
                ],
            ])
            ->assertSessionHas('success');

        $this->actingAs($ctx['customer'])
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $ctx['employee']->id,
                'department_id' => $ctx['department']->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $ctx['delivery']->id,
                'items' => [
                    ['uniform_product_id' => $ctx['top']->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, Application::where('employee_id', $ctx['employee']->id)->count());
    }

    public function test_special_approval_accept_clamps_stock_at_zero(): void
    {
        $ctx = $this->seedFullContext(rentableTop: 5, stock: 0);

        $this->actingAs($ctx['customer'])
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $ctx['employee']->id,
                'department_id' => $ctx['department']->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $ctx['delivery']->id,
                'items' => [
                    ['uniform_product_id' => $ctx['top']->id, 'quantity' => 1],
                ],
                'special_approval_reason' => 'Urgent need — backorder ok',
            ])
            ->assertSessionHas('success');

        $app = Application::where('employee_id', $ctx['employee']->id)->first();
        $this->assertSame(Application::STATUS_WAITING_SPECIAL, $app->status);

        $this->actingAs($ctx['admin'])
            ->post(route('admin.applications.approve', $app))
            ->assertRedirect();

        $ctx['top']->refresh();
        $this->assertSame(0, (int) $ctx['top']->stock_quantity);

        $loan = EmployeeLoan::where('employee_id', $ctx['employee']->id)
            ->where('status', 'in_use')
            ->first();
        $this->assertSame(1, (int) $loan->quantity);
    }

    public function test_contract_plan_update_preserves_slot_category(): void
    {
        $ctx = $this->seedFullContext();

        $plan = $ctx['plan'];
        $this->assertSame(
            'top',
            $plan->uniformProducts()->where('uniform_products.id', $ctx['top']->id)->first()->pivot->slot_category
        );

        $this->actingAs($ctx['admin'])
            ->put(route('admin.contract-plans.update', $plan), [
                'name' => $plan->name,
                'rentable_points_top' => 5,
                'rentable_points_bottom' => 3,
                'unit_price_ex_tax' => 1000,
                'is_active' => 1,
                'uniform_product_ids' => [$ctx['top']->id, $ctx['bottom']->id],
            ])
            ->assertRedirect(route('admin.contract-plans.index'));

        $plan->refresh();
        $this->assertSame(
            'top',
            $plan->uniformProducts()->where('uniform_products.id', $ctx['top']->id)->first()->pivot->slot_category
        );
        $this->assertSame(
            'bottom',
            $plan->uniformProducts()->where('uniform_products.id', $ctx['bottom']->id)->first()->pivot->slot_category
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seedFullContext(int $rentableTop = 5, int $stock = 10): array
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-E2E',
            'onoe_code' => 'ON-E2E',
            'name' => 'E2E Hospital',
            'notification_email' => 'e2e@example.com',
            'billing_closing_day' => 1,
        ]);
        $department = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'D-E',
            'name' => 'Dept',
        ]);
        $delivery = DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => 'Dock',
        ]);
        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'primary_department_id' => $department->id,
            'staff_no' => 'E-E2E',
            'name' => 'Worker',
            'status' => 'employed',
            'join_date' => now()->subMonths(3)->toDateString(),
        ]);

        $maker = Maker::create(['code' => 'MK-E', 'name' => 'Maker']);
        $top = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-TOP-E',
            'name' => 'Top',
            'category' => 'top',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => $stock,
        ]);
        $bottom = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-BOT-E',
            'name' => 'Bottom',
            'category' => 'bottom',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => $stock,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-E2E',
            'name' => 'Plan E2E',
            'rentable_points' => $rentableTop + 3,
            'rentable_points_top' => $rentableTop,
            'rentable_points_bottom' => 3,
            'unit_price_ex_tax' => 2500,
            'is_active' => true,
        ]);
        $plan->uniformProducts()->sync([
            $top->id => ['slot_category' => 'top'],
            $bottom->id => ['slot_category' => 'bottom'],
        ]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        EmployeeContractPlan::create([
            'employee_id' => $employee->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => now()->subMonths(2)->toDateString(),
        ]);

        $customerUser = User::factory()->create(['type' => 'customer', 'email' => 'e2e-c@example.com']);
        CustomerUser::create([
            'user_id' => $customerUser->id,
            'medical_institution_id' => $institution->id,
            'role' => 'institution_admin',
        ]);

        $adminUser = User::factory()->create(['type' => 'admin', 'email' => 'e2e-a@example.com']);
        AdminUser::create([
            'user_id' => $adminUser->id,
            'role' => 'system_admin',
        ]);

        return [
            'institution' => $institution,
            'department' => $department,
            'delivery' => $delivery,
            'employee' => $employee,
            'top' => $top,
            'bottom' => $bottom,
            'plan' => $plan,
            'customer' => $customerUser,
            'admin' => $adminUser,
        ];
    }
}
