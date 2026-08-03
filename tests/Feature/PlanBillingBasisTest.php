<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ContractPlan;
use App\Models\CustomerContractPlan;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\MedicalInstitution;
use App\Models\User;
use App\Services\PlanBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanBillingBasisTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_bill_is_assigned_headcount_times_unit_price_as_of_closing_day(): void
    {
        $institution = MedicalInstitution::create([
            'name' => 'Test Hospital',
            'code' => 'H-BILL',
            'notification_email' => 'h@example.com',
            'billing_closing_day' => 1,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-B1',
            'name' => 'Nurse 5',
            'rentable_points' => 5,
            'unit_price_ex_tax' => 1000,
            'is_active' => true,
        ]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 10, // capacity reference — not the bill multiplier
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $e1 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => 'E1',
            'name' => 'A',
            'status' => 'employed',
            'join_date' => '2025-01-01',
        ]);
        $e2 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => 'E2',
            'name' => 'B',
            'status' => 'employed',
            'join_date' => '2025-01-01',
        ]);
        // Retired before closing day — must not bill
        $e3 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => 'E3',
            'name' => 'C',
            'status' => 'retired',
            'join_date' => '2025-01-01',
            'retire_date' => '2026-07-01',
        ]);

        foreach ([$e1, $e2, $e3] as $emp) {
            EmployeeContractPlan::create([
                'employee_id' => $emp->id,
                'customer_contract_plan_id' => $ccp->id,
                'assigned_from' => '2026-01-01',
            ]);
        }

        $data = app(PlanBillingService::class)->build($institution, '2026-08-01');

        $this->assertSame('plan_based', $data['model']);
        $this->assertSame('2026-08-01', $data['reference_date']);
        $this->assertSame(2, $data['summary']['total_assigned']);
        $this->assertSame(2000.0, (float) $data['summary']['billing_amount']); // 2 × 1000, not 10 × 1000
        $this->assertSame(10, $data['summary']['total_contract_slots']);
        $this->assertSame(10000.0, (float) $data['summary']['contract_capacity_amount']);
        $this->assertCount(2, $data['staff_rows']);
    }

    public function test_inactive_customer_contract_after_end_date_is_excluded(): void
    {
        $institution = MedicalInstitution::create([
            'name' => 'Test Hospital 2',
            'code' => 'H-BILL2',
            'notification_email' => 'h2@example.com',
            'billing_closing_day' => 1,
        ]);

        $plan = ContractPlan::create([
            'code' => 'PLN-B2',
            'name' => 'Plan',
            'rentable_points' => 3,
            'unit_price_ex_tax' => 500,
            'is_active' => true,
        ]);

        $ccp = CustomerContractPlan::create([
            'medical_institution_id' => $institution->id,
            'contract_plan_id' => $plan->id,
            'contract_count' => 5,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30', // ended before August closing
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $emp = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => 'E9',
            'name' => 'X',
            'status' => 'employed',
            'join_date' => '2025-01-01',
        ]);

        EmployeeContractPlan::create([
            'employee_id' => $emp->id,
            'customer_contract_plan_id' => $ccp->id,
            'assigned_from' => '2026-01-01',
        ]);

        $data = app(PlanBillingService::class)->build($institution, '2026-08-01');

        $this->assertSame(0, $data['summary']['total_assigned']);
        $this->assertSame(0.0, (float) $data['summary']['billing_amount']);
        $this->assertSame([], $data['plan_summary']);
    }
}
