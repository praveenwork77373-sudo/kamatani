<?php

namespace Tests\Feature;

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
use App\Services\BaseDataImportService;
use App\Services\EmployeePlanEligibilityService;
use App\Services\PlanBillingExportService;
use App\Services\PlanBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcelClientRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_cap_blocks_over_limit_even_when_total_has_room(): void
    {
        [$institution, $department, $delivery, $employee, $topProduct, $bottomProduct, $customer] = $this->seedCategoryContext();

        // Already at top cap (2). Adding another top must fail even though bottom cap has room.
        EmployeeLoan::create([
            'employee_id' => $employee->id,
            'uniform_product_id' => $topProduct->id,
            'quantity' => 2,
            'status' => 'in_use',
            'loan_start_date' => now()->subWeek()->toDateString(),
        ]);

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
                    ['uniform_product_id' => $topProduct->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('applications', [
            'employee_id' => $employee->id,
            'type' => 'new_loan',
        ]);

        $response = $this->actingAs($customer)
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'application_mode' => 'individual',
                'employee_id' => $employee->id,
                'department_id' => $department->id,
                'type' => 'new_loan',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'delivery_location_id' => $delivery->id,
                'items' => [
                    ['uniform_product_id' => $bottomProduct->id, 'quantity' => 1],
                ],
            ]);

        $response->assertRedirect(route('applications.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'employee_id' => $employee->id,
            'type' => 'new_loan',
        ]);
    }

    public function test_products_without_usable_size_are_hidden_from_eligibility(): void
    {
        [$institution, $department, $delivery, $employee, $topProduct, $bottomProduct, $customer] = $this->seedCategoryContext();

        $noSize = UniformProduct::create([
            'maker_id' => $topProduct->maker_id,
            'product_code' => 'P-NOSIZE',
            'name' => 'No Size Item',
            'category' => 'other',
            'size' => '-',
            'gender' => 'unisex',
            'stock_quantity' => 5,
        ]);
        $oneSizeF = UniformProduct::create([
            'maker_id' => $topProduct->maker_id,
            'product_code' => 'P-F',
            'name' => 'One Size F',
            'category' => 'other',
            'size' => 'F',
            'gender' => 'unisex',
            'stock_quantity' => 5,
        ]);

        $plan = ContractPlan::where('code', 'PLN-CAT')->first();
        $plan->uniformProducts()->attach([
            $noSize->id => ['slot_category' => 'other'],
            $oneSizeF->id => ['slot_category' => 'other'],
        ]);

        $eligible = app(EmployeePlanEligibilityService::class)->eligibleProducts($employee);
        $ids = $eligible->pluck('id')->all();

        $this->assertNotContains($noSize->id, $ids);
        $this->assertContains($oneSizeF->id, $ids);
        $this->assertContains($topProduct->id, $ids);
    }

    public function test_partner_unit_price_is_stored_but_not_used_in_billing(): void
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-PART',
            'onoe_code' => 'ON-1',
            'name' => 'Partner Hospital',
            'notification_email' => 'p@example.com',
            'billing_closing_day' => 1,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-P',
            'name' => 'Plan P',
            'rentable_points' => 3,
            'rentable_points_top' => 3,
            'unit_price_ex_tax' => 2000,
            'partner_unit_price_ex_tax' => 9999,
            'is_active' => true,
        ]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 5,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => 'E-P1',
            'name' => 'Staff',
            'status' => 'employed',
            'join_date' => now()->subMonths(2)->toDateString(),
        ]);
        EmployeeContractPlan::create([
            'employee_id' => $employee->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => now()->subMonth()->toDateString(),
        ]);

        $data = app(PlanBillingService::class)->build($institution, now());
        $this->assertSame(2000.0, (float) ($data['summary']['billing_amount'] ?? 0));
        $this->assertSame('ON-1', $data['institution']['onoe_code'] ?? null);
        $this->assertSame('H-PART', $data['institution']['code'] ?? null);

        $spreadsheet = app(PlanBillingExportService::class)->buildSpreadsheet(
            $data,
            now()->toDateString(),
            $institution->name
        );
        $this->assertSame('得意先コード', $spreadsheet->getSheet(0)->getCell('C2')->getValue());
        $this->assertSame('H-PART', $spreadsheet->getSheet(0)->getCell('D2')->getValue());
        $this->assertSame('オノエコード', $spreadsheet->getSheet(0)->getCell('E2')->getValue());
        $this->assertSame('ON-1', $spreadsheet->getSheet(0)->getCell('F2')->getValue());
    }

    public function test_confirmed_ccp_requires_start_and_end_dates(): void
    {
        $admin = User::factory()->create(['type' => 'admin', 'email' => 'ccp-admin@example.com']);
        \App\Models\AdminUser::create(['user_id' => $admin->id, 'role' => 'system_admin']);

        $institution = MedicalInstitution::create([
            'code' => 'H-DATE',
            'name' => 'Date Hospital',
            'notification_email' => 'd@example.com',
        ]);
        $plan = ContractPlan::create([
            'code' => 'PLN-D',
            'name' => 'Plan D',
            'rentable_points' => 1,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.customer-contract-plans.store'), [
                'medical_institution_id' => $institution->id,
                'contract_plan_id' => $plan->id,
                'contract_count' => 1,
                'status' => 'confirmed',
                // missing dates
            ])
            ->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_base_import_stores_category_caps(): void
    {
        $matches = glob(base_path('clientdocs/260805*.xlsx')) ?: [];
        $this->assertNotEmpty($matches);

        app(BaseDataImportService::class)->import($matches[0]);

        $plan = ContractPlan::where('code', '30170300-R0101')->first();
        $this->assertNotNull($plan);
        $this->assertSame(5, (int) $plan->rentable_points_top);
        $this->assertSame(5, (int) $plan->rentable_points_bottom);
        $this->assertSame(5, (int) $plan->rentable_points_other);
        $this->assertSame(15, (int) $plan->rentable_points);
        $this->assertTrue(
            $plan->uniformProducts()->wherePivot('slot_category', 'top')->exists()
        );
    }

    /**
     * @return array{0: MedicalInstitution, 1: Department, 2: DeliveryLocation, 3: Employee, 4: UniformProduct, 5: UniformProduct, 6: User}
     */
    private function seedCategoryContext(): array
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-CAT',
            'name' => 'Category Hospital',
            'notification_email' => 'c@example.com',
            'billing_closing_day' => 1,
        ]);
        $department = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'D-C',
            'name' => 'Dept',
        ]);
        $delivery = DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => 'Dock',
        ]);
        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'primary_department_id' => $department->id,
            'staff_no' => 'E-C1',
            'name' => 'Worker',
            'status' => 'employed',
        ]);

        $maker = Maker::create(['code' => 'MK-C', 'name' => 'Maker']);
        $top = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-TOP',
            'name' => 'Top',
            'category' => 'top',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => 20,
        ]);
        $bottom = UniformProduct::create([
            'maker_id' => $maker->id,
            'product_code' => 'P-BOT',
            'name' => 'Bottom',
            'category' => 'bottom',
            'size' => 'M',
            'gender' => 'unisex',
            'stock_quantity' => 20,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-CAT',
            'name' => 'Plan Cat',
            'rentable_points' => 5,
            'rentable_points_top' => 2,
            'rentable_points_bottom' => 3,
            'rentable_points_other' => 0,
            'rentable_points_other2' => 0,
            'rentable_points_other3' => 0,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);
        $plan->uniformProducts()->attach([
            $top->id => ['slot_category' => 'top'],
            $bottom->id => ['slot_category' => 'bottom'],
        ]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10,
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

        $user = User::factory()->create(['type' => 'customer', 'email' => 'cat-c@example.com']);
        CustomerUser::create([
            'user_id' => $user->id,
            'medical_institution_id' => $institution->id,
            'role' => 'institution_admin',
        ]);

        return [$institution, $department, $delivery, $employee, $top, $bottom, $user];
    }
}
