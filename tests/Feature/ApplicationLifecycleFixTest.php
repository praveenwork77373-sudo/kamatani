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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationLifecycleFixTest extends TestCase
{
    use RefreshDatabase;

    private MedicalInstitution $institution;
    private Department $department;
    private DeliveryLocation $delivery;
    private Employee $employee;
    private Employee $transferTo;
    private UniformProduct $product;
    private User $customer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = MedicalInstitution::create([
            'code' => 'H-100',
            'name' => 'テスト病院',
            'notification_email' => 'notify@example.com',
            'billing_closing_day' => 1,
        ]);
        $this->department = Department::create([
            'medical_institution_id' => $this->institution->id,
            'code' => 'D-100',
            'name' => '内科',
        ]);
        $this->delivery = DeliveryLocation::create([
            'medical_institution_id' => $this->institution->id,
            'name' => '本館',
        ]);
        $this->employee = Employee::create([
            'medical_institution_id' => $this->institution->id,
            'primary_department_id' => $this->department->id,
            'staff_no' => 'E100',
            'name' => '申請者',
            'status' => 'employed',
        ]);
        $this->transferTo = Employee::create([
            'medical_institution_id' => $this->institution->id,
            'primary_department_id' => $this->department->id,
            'staff_no' => 'E200',
            'name' => '移管先',
            'status' => 'employed',
        ]);

        $maker = Maker::create(['code' => 'MK1', 'name' => 'Maker']);
        $this->product = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-1',
            'name' => 'スクラブ',
            'category' => 'top',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => 10,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-1',
            'name' => 'Plan A',
            'rentable_points' => 5,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);
        $plan->uniformProducts()->attach($this->product->id);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $this->institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        foreach ([$this->employee, $this->transferTo] as $emp) {
            EmployeeContractPlan::create([
                'employee_id' => $emp->id,
                'customer_contract_plan_id' => $ccp->id,
                'assigned_from' => now()->subMonth()->toDateString(),
            ]);
        }

        EmployeeLoan::create([
            'employee_id' => $this->employee->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 2,
            'status' => 'in_use',
            'loan_start_date' => now()->subWeek()->toDateString(),
        ]);

        $this->customer = User::factory()->create(['type' => 'customer', 'email' => 'c@example.com']);
        CustomerUser::create([
            'user_id' => $this->customer->id,
            'medical_institution_id' => $this->institution->id,
            'role' => 'institution_admin',
        ]);

        $this->admin = User::factory()->create(['type' => 'admin', 'email' => 'a@example.com']);
        AdminUser::create(['user_id' => $this->admin->id, 'role' => 'operation']);
    }

    public function test_return_create_accepts_return_items_only(): void
    {
        $this->actingAs($this->customer)
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $this->employee->id,
                'department_id' => $this->department->id,
                'type' => 'return',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'delivery_location_id' => $this->delivery->id,
                'return_items' => [
                    ['uniform_product_id' => $this->product->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('applications.create'));

        $app = Application::where('type', 'return')->latest('id')->first();
        $this->assertNotNull($app);
        $this->assertSame('pending', $app->status);
        $this->assertCount(1, $app->items);
        $this->assertSame('return', $app->items->first()->action);
    }

    public function test_transfer_create_normalizes_return_items_into_items(): void
    {
        $this->actingAs($this->customer)
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $this->employee->id,
                'transfer_to_employee_id' => $this->transferTo->id,
                'department_id' => $this->department->id,
                'type' => 'transfer',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'delivery_location_id' => $this->delivery->id,
                'return_items' => [
                    ['uniform_product_id' => $this->product->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('applications.create'));

        $app = Application::where('type', 'transfer')->latest('id')->first();
        $this->assertNotNull($app);
        $this->assertSame($this->transferTo->id, $app->transfer_to_employee_id);
        $this->assertCount(1, $app->items);
        $this->assertSame('transfer_change', $app->items->first()->action);
    }

    public function test_cancel_reject_restores_in_progress_and_allows_complete(): void
    {
        $app = Application::create([
            'medical_institution_id' => $this->institution->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->employee->id,
            'type' => 'new_loan',
            'status' => 'cancel_request',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'delivery_location_id' => $this->delivery->id,
            'requested_by_user_id' => $this->customer->id,
            'accepted_at' => now()->subHour(),
            'approved_at' => now()->subHour(),
        ]);
        ApplicationItem::create([
            'application_id' => $app->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 1,
            'action' => 'loan',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.applications.cancel_reject', $app))
            ->assertRedirect();

        $app->refresh();
        $this->assertSame(Application::STATUS_IN_PROGRESS, $app->status);

        $this->actingAs($this->admin)
            ->post(route('admin.applications.complete', $app))
            ->assertRedirect();

        $app->refresh();
        $this->assertSame(Application::STATUS_COMPLETED, $app->status);
    }

    public function test_legacy_cancel_rejected_can_still_complete(): void
    {
        $app = Application::create([
            'medical_institution_id' => $this->institution->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->employee->id,
            'type' => 'new_loan',
            'status' => 'cancel_rejected',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'delivery_location_id' => $this->delivery->id,
            'requested_by_user_id' => $this->customer->id,
            'accepted_at' => now()->subHour(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.applications.complete', $app))
            ->assertRedirect();

        $this->assertSame(Application::STATUS_COMPLETED, $app->fresh()->status);
    }

    public function test_transfer_approval_moves_loan(): void
    {
        $app = Application::create([
            'medical_institution_id' => $this->institution->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->employee->id,
            'transfer_to_employee_id' => $this->transferTo->id,
            'type' => 'transfer',
            'status' => 'pending',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'delivery_location_id' => $this->delivery->id,
            'requested_by_user_id' => $this->customer->id,
        ]);
        ApplicationItem::create([
            'application_id' => $app->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 1,
            'action' => 'transfer_change',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.applications.approve', $app))
            ->assertRedirect();

        $from = EmployeeLoan::where('employee_id', $this->employee->id)
            ->where('uniform_product_id', $this->product->id)
            ->first();
        $to = EmployeeLoan::where('employee_id', $this->transferTo->id)
            ->where('uniform_product_id', $this->product->id)
            ->where('status', 'in_use')
            ->first();

        $this->assertSame(1, (int) $from->quantity);
        $this->assertNotNull($to);
        $this->assertSame(1, (int) $to->quantity);
        $this->assertSame(Application::STATUS_IN_PROGRESS, $app->fresh()->status);
    }
}
