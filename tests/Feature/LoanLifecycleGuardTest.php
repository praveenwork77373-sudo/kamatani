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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanLifecycleGuardTest extends TestCase
{
    use RefreshDatabase;

    private MedicalInstitution $institution;
    private Department $department;
    private DeliveryLocation $delivery;
    private Employee $employee;
    private UniformProduct $product;
    private User $customer;
    private User $admin;
    private EmployeeContractPlan $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ApplicationPdfService::class, function ($mock) {
            $mock->shouldReceive('issueAll')->andReturn(collect());
        });

        $this->institution = MedicalInstitution::create([
            'code' => 'H-200',
            'name' => 'Lifecycle Hospital',
            'notification_email' => 'notify@example.com',
            'billing_closing_day' => 1,
        ]);
        $this->department = Department::create([
            'medical_institution_id' => $this->institution->id,
            'code' => 'D-200',
            'name' => '外科',
        ]);
        $this->delivery = DeliveryLocation::create([
            'medical_institution_id' => $this->institution->id,
            'name' => '本館',
        ]);
        $this->employee = Employee::create([
            'medical_institution_id' => $this->institution->id,
            'primary_department_id' => $this->department->id,
            'staff_no' => 'E200',
            'name' => '貸与職員',
            'status' => 'employed',
        ]);

        $maker = Maker::create(['code' => 'MK2', 'name' => 'Maker']);
        $this->product = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-L1',
            'name' => 'スクラブ',
            'category' => 'top',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => 5,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-L1',
            'name' => 'Plan L',
            'rentable_points' => 5,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);
        $plan->uniformProducts()->attach([$this->product->id]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $this->institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->assignment = EmployeeContractPlan::create([
            'employee_id' => $this->employee->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => now()->subMonth()->toDateString(),
        ]);

        EmployeeLoan::create([
            'employee_id' => $this->employee->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 2,
            'status' => 'in_use',
            'loan_start_date' => now()->subWeek()->toDateString(),
        ]);

        $this->customer = User::factory()->create(['type' => 'customer', 'email' => 'life-c@example.com']);
        CustomerUser::create([
            'user_id' => $this->customer->id,
            'medical_institution_id' => $this->institution->id,
            'role' => 'institution_admin',
        ]);

        $this->admin = User::factory()->create(['type' => 'admin', 'email' => 'life-a@example.com']);
        AdminUser::create(['user_id' => $this->admin->id, 'role' => 'operation']);
    }

    public function test_cannot_delete_employee_with_active_loans(): void
    {
        $this->actingAs($this->customer)
            ->delete(route('employees.destroy', $this->employee))
            ->assertRedirect(route('employees.show', $this->employee))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('employees', ['id' => $this->employee->id]);
        $this->assertDatabaseHas('employee_loans', [
            'employee_id' => $this->employee->id,
            'status' => 'in_use',
            'quantity' => 2,
        ]);
        $this->assertSame(5, (int) $this->product->fresh()->stock_quantity);
    }

    public function test_can_delete_employee_after_loans_returned(): void
    {
        EmployeeLoan::where('employee_id', $this->employee->id)->update([
            'quantity' => 0,
            'status' => 'returned',
            'loan_end_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->customer)
            ->delete(route('employees.destroy', $this->employee))
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('employees', ['id' => $this->employee->id]);
    }

    public function test_cannot_retire_employee_with_active_loans(): void
    {
        $this->actingAs($this->customer)
            ->put(route('employees.update', $this->employee), [
                'name' => $this->employee->name,
                'status' => 'retired',
                'retire_date' => now()->toDateString(),
                'primary_department_id' => $this->department->id,
                'customer_contract_plan_ids' => [$this->assignment->customer_contract_plan_id],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('employed', $this->employee->fresh()->status);
    }

    public function test_return_quantity_cannot_exceed_held_loan(): void
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
                    ['uniform_product_id' => $this->product->id, 'quantity' => 5],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Application::where('type', 'return')->count());
    }

    public function test_return_allowed_without_active_plan_assignment(): void
    {
        $this->assignment->update(['assigned_until' => now()->subDay()->toDateString()]);

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

        $this->assertSame(1, Application::where('type', 'return')->count());
    }

    public function test_return_approval_restocks_only_held_quantity(): void
    {
        $app = Application::create([
            'medical_institution_id' => $this->institution->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->employee->id,
            'type' => 'return',
            'status' => 'pending',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'delivery_location_id' => $this->delivery->id,
            'requested_by_user_id' => $this->customer->id,
        ]);
        // Over-request on purpose (should only restock 2)
        ApplicationItem::create([
            'application_id' => $app->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 9,
            'action' => 'return',
        ]);

        $stockBefore = (int) $this->product->fresh()->stock_quantity;

        $this->actingAs($this->admin)
            ->post(route('admin.applications.approve', $app))
            ->assertRedirect();

        $loan = EmployeeLoan::where('employee_id', $this->employee->id)
            ->where('uniform_product_id', $this->product->id)
            ->first();

        $this->assertSame('returned', $loan->status);
        $this->assertSame(0, (int) $loan->quantity);
        $this->assertSame($stockBefore + 2, (int) $this->product->fresh()->stock_quantity);
    }

    public function test_happy_path_return_then_delete_employee(): void
    {
        $app = Application::create([
            'medical_institution_id' => $this->institution->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->employee->id,
            'type' => 'return',
            'status' => 'pending',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'delivery_location_id' => $this->delivery->id,
            'requested_by_user_id' => $this->customer->id,
        ]);
        ApplicationItem::create([
            'application_id' => $app->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 2,
            'action' => 'return',
        ]);

        $stockBefore = (int) $this->product->fresh()->stock_quantity;

        $this->actingAs($this->admin)
            ->post(route('admin.applications.approve', $app))
            ->assertRedirect();

        $this->assertSame($stockBefore + 2, (int) $this->product->fresh()->stock_quantity);
        $this->assertSame('returned', EmployeeLoan::where('employee_id', $this->employee->id)->first()->status);

        $this->actingAs($this->customer)
            ->delete(route('employees.destroy', $this->employee))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $this->employee->id]);
        $this->assertSame($stockBefore + 2, (int) $this->product->fresh()->stock_quantity);
    }

    public function test_cannot_delete_product_with_active_loans(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.uniform-products.destroy', $this->product))
            ->assertRedirect(route('admin.uniform-products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('uniform_products', ['id' => $this->product->id]);
    }

    public function test_cancel_after_return_approval_restores_loan_and_stock(): void
    {
        $app = Application::create([
            'medical_institution_id' => $this->institution->id,
            'department_id' => $this->department->id,
            'employee_id' => $this->employee->id,
            'type' => 'return',
            'status' => 'pending',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'delivery_location_id' => $this->delivery->id,
            'requested_by_user_id' => $this->customer->id,
        ]);
        ApplicationItem::create([
            'application_id' => $app->id,
            'uniform_product_id' => $this->product->id,
            'quantity' => 2,
            'action' => 'return',
        ]);

        $stockBefore = (int) $this->product->fresh()->stock_quantity;

        $this->actingAs($this->admin)
            ->post(route('admin.applications.approve', $app))
            ->assertRedirect();

        $this->assertSame($stockBefore + 2, (int) $this->product->fresh()->stock_quantity);

        $app->refresh();
        $app->update(['status' => 'cancel_request']);

        $this->actingAs($this->admin)
            ->post(route('admin.applications.cancel_approve', $app))
            ->assertRedirect()
            ->assertSessionHas('status');

        $loan = EmployeeLoan::where('employee_id', $this->employee->id)
            ->where('uniform_product_id', $this->product->id)
            ->first();

        $this->assertSame('in_use', $loan->status);
        $this->assertSame(2, (int) $loan->quantity);
        $this->assertSame($stockBefore, (int) $this->product->fresh()->stock_quantity);
        $this->assertSame('cancel_approved', $app->fresh()->status);
    }
}
