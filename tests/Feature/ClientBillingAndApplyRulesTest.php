<?php

namespace Tests\Feature;

use App\Models\BillingSnapshot;
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
use App\Services\BillingGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientBillingAndApplyRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rentable_over_limit_is_hard_blocked(): void
    {
        [$institution, $department, $delivery, $employee, $product, $customer] = $this->seedAppContext(rentablePoints: 2, existingLoanQty: 2);

        $this->actingAs($customer)
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $employee->id,
                'department_id' => $department->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $delivery->id,
                'items' => [
                    ['uniform_product_id' => $product->id, 'quantity' => 1],
                ],
                'special_approval_reason' => 'Trying to bypass',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('applications', [
            'employee_id' => $employee->id,
            'type' => 'new_loan',
        ]);
    }

    public function test_departmental_mode_is_rejected(): void
    {
        [$institution, $department, $delivery, $employee, $product, $customer] = $this->seedAppContext();

        $this->actingAs($customer)
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'application_mode' => 'departmental',
                'employee_id' => $employee->id,
                'department_id' => $department->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $delivery->id,
                'items' => [
                    ['uniform_product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors('application_mode');
    }

    public function test_closing_day_auto_generate_creates_snapshot(): void
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-AUTO',
            'name' => 'Auto Bill Hospital',
            'notification_email' => 'a@example.com',
            'billing_closing_day' => 6,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-A',
            'name' => 'Plan A',
            'rentable_points' => 5,
            'unit_price_ex_tax' => 3000,
            'is_active' => true,
        ]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => 'E-A1',
            'name' => 'Staff',
            'status' => 'employed',
            'join_date' => now()->subMonths(2)->toDateString(),
        ]);

        EmployeeContractPlan::create([
            'employee_id' => $employee->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => now()->subMonth()->toDateString(),
        ]);

        $asOf = now()->day(6)->startOfDay();
        $result = app(BillingGenerationService::class)->generateDueClosingDay($asOf);

        $this->assertSame(1, $result['generated']);
        $this->assertTrue(
            BillingSnapshot::query()
                ->where('medical_institution_id', $institution->id)
                ->whereDate('target_date', $asOf->toDateString())
                ->exists()
        );

        $snapshot = BillingSnapshot::first();
        $this->assertSame(1, $snapshot->data['summary']['total_assigned'] ?? null);
        $this->assertSame(10, $snapshot->data['summary']['total_contract_slots'] ?? null);
        $this->assertSame(3000.0, (float) ($snapshot->data['summary']['billing_amount'] ?? 0));
    }

    /**
     * @return array{0: MedicalInstitution, 1: Department, 2: DeliveryLocation, 3: Employee, 4: UniformProduct, 5: User}
     */
    private function seedAppContext(int $rentablePoints = 5, int $existingLoanQty = 0): array
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-RULE',
            'name' => 'Rule Hospital',
            'notification_email' => 'r@example.com',
            'billing_closing_day' => 1,
        ]);
        $department = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'D-R',
            'name' => 'Dept',
        ]);
        $delivery = DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => 'Dock',
        ]);
        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'primary_department_id' => $department->id,
            'staff_no' => 'E-R1',
            'name' => 'Worker',
            'status' => 'employed',
        ]);

        $maker = Maker::create(['code' => 'MK-R', 'name' => 'Maker']);
        $product = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-R1',
            'name' => 'Scrub',
            'category' => 'top',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => 20,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-R',
            'name' => 'Plan R',
            'rentable_points' => $rentablePoints,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);
        $plan->uniformProducts()->attach([$product->id]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        EmployeeContractPlan::create([
            'employee_id' => $employee->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => now()->subMonth()->toDateString(),
        ]);

        if ($existingLoanQty > 0) {
            EmployeeLoan::create([
                'employee_id' => $employee->id,
                'uniform_product_id' => $product->id,
                'quantity' => $existingLoanQty,
                'status' => 'in_use',
                'loan_start_date' => now()->subWeek()->toDateString(),
            ]);
        }

        $user = User::factory()->create(['type' => 'customer', 'email' => 'rule-c@example.com']);
        CustomerUser::create([
            'user_id' => $user->id,
            'medical_institution_id' => $institution->id,
            'role' => 'institution_admin',
        ]);

        return [$institution, $department, $delivery, $employee, $product, $user];
    }
}
