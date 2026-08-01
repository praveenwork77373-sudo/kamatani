<?php

namespace App\Services;

use App\Models\CustomerContractPlan;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\MedicalInstitution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlanBillingService
{
    /**
     * Default as-of date for a year-month using hospital closing day.
     */
    public function defaultAsOfDate(MedicalInstitution $institution, ?string $yearMonth = null): Carbon
    {
        $ym = $yearMonth ? Carbon::parse($yearMonth.'-01') : now();
        $day = (int) ($institution->billing_closing_day ?: 1);
        $day = max(1, min(28, $day));
        $lastDay = $ym->copy()->endOfMonth()->day;
        $day = min($day, $lastDay);

        return $ym->copy()->day($day)->startOfDay();
    }

    public function isEmployedAsOf(Employee $employee, Carbon|string $asOf): bool
    {
        $date = Carbon::parse($asOf)->toDateString();

        if ($employee->join_date && $employee->join_date->toDateString() > $date) {
            return false;
        }
        if ($employee->retire_date && $employee->retire_date->toDateString() <= $date) {
            return false;
        }

        $status = $employee->getStatusAsOf($date);

        return $status === 'employed';
    }

    /**
     * Build plan-based billing payload for snapshot + Excel.
     */
    public function build(MedicalInstitution $institution, Carbon|string $asOf): array
    {
        $asOf = Carbon::parse($asOf)->startOfDay();
        $asOfStr = $asOf->toDateString();

        $employees = Employee::query()
            ->where('medical_institution_id', $institution->id)
            ->with(['primaryDepartment', 'contractPlanAssignments.customerContractPlan.contractPlan'])
            ->orderBy('staff_no')
            ->get();

        $staffRows = [];
        $planAgg = []; // key: customer_contract_plan_id or plan_id

        foreach ($employees as $employee) {
            if (! $this->isEmployedAsOf($employee, $asOf)) {
                continue;
            }

            /** @var Collection<int, EmployeeContractPlan> $assignments */
            $assignments = $employee->contractPlanAssignments ?? collect();

            foreach ($assignments as $assignment) {
                if (! $assignment->isActiveOn($asOfStr)) {
                    continue;
                }

                $ccp = $assignment->customerContractPlan;
                if (! $ccp || ! $ccp->isActiveOn($asOfStr)) {
                    continue;
                }

                $plan = $ccp->contractPlan;
                if (! $plan) {
                    continue;
                }

                $unitPrice = (float) ($plan->unit_price_ex_tax ?? 0);
                $contractCount = 1; // per assigned employed staff row
                $amount = $contractCount * $unitPrice;

                $staffRows[] = [
                    'reference_date' => $asOfStr,
                    'hospital_name' => $institution->name,
                    'hospital_code' => $institution->code,
                    'staff_no' => $employee->staff_no,
                    'person_name' => $employee->name,
                    'department_name' => $employee->primaryDepartment?->name,
                    'join_date' => optional($employee->join_date)->format('Y-m-d'),
                    'retire_date' => optional($employee->retire_date)->format('Y-m-d'),
                    'plan_name' => $plan->name,
                    'plan_code' => $plan->code,
                    'customer_contract_plan_id' => $ccp->id,
                    'contracted_slots' => (int) $ccp->contract_count,
                    'contract_count' => $contractCount,
                    'unit_price_ex_tax' => $unitPrice,
                    'amount_ex_tax' => $amount,
                    'job_type_label' => $plan->job_type_label,
                    'rentable_points' => $plan->rentable_points,
                    'work_style' => $plan->work_style,
                ];

                $key = (string) $ccp->id;
                if (! isset($planAgg[$key])) {
                    $planAgg[$key] = [
                        'customer_contract_plan_id' => $ccp->id,
                        'plan_name' => $plan->name,
                        'plan_code' => $plan->code,
                        'job_type_label' => $plan->job_type_label,
                        'rentable_points' => $plan->rentable_points,
                        'work_style' => $plan->work_style,
                        'contracted_slots' => (int) $ccp->contract_count,
                        'assigned_count' => 0,
                        'unit_price_ex_tax' => $unitPrice,
                        'amount_ex_tax' => 0,
                        'amount_by_contract_slots' => (int) $ccp->contract_count * $unitPrice,
                    ];
                }
                $planAgg[$key]['assigned_count']++;
                $planAgg[$key]['amount_ex_tax'] = $planAgg[$key]['assigned_count'] * $planAgg[$key]['unit_price_ex_tax'];
                $planAgg[$key]['amount_by_contract_slots'] = $planAgg[$key]['contracted_slots'] * $planAgg[$key]['unit_price_ex_tax'];
            }
        }

        // Include confirmed plans with zero assignment (capacity still visible)
        $confirmedCcps = CustomerContractPlan::query()
            ->with('contractPlan')
            ->where('medical_institution_id', $institution->id)
            ->where('status', 'confirmed')
            ->get()
            ->filter(fn (CustomerContractPlan $ccp) => $ccp->isActiveOn($asOfStr));

        foreach ($confirmedCcps as $ccp) {
            $key = (string) $ccp->id;
            if (isset($planAgg[$key])) {
                continue;
            }
            $plan = $ccp->contractPlan;
            if (! $plan) {
                continue;
            }
            $unitPrice = (float) ($plan->unit_price_ex_tax ?? 0);
            $slots = (int) $ccp->contract_count;
            $planAgg[$key] = [
                'customer_contract_plan_id' => $ccp->id,
                'plan_name' => $plan->name,
                'plan_code' => $plan->code,
                'job_type_label' => $plan->job_type_label,
                'rentable_points' => $plan->rentable_points,
                'work_style' => $plan->work_style,
                'contracted_slots' => $slots,
                'assigned_count' => 0,
                'unit_price_ex_tax' => $unitPrice,
                'amount_ex_tax' => 0,
                'amount_by_contract_slots' => $slots * $unitPrice,
            ];
        }

        $planSummary = array_values($planAgg);
        usort($planSummary, fn ($a, $b) => strcmp($a['plan_code'] ?? '', $b['plan_code'] ?? ''));

        $totalAmount = array_sum(array_column($planSummary, 'amount_ex_tax'));
        $totalAssigned = array_sum(array_column($planSummary, 'assigned_count'));
        $totalContractSlots = array_sum(array_column($planSummary, 'contracted_slots'));
        $totalCapacityAmount = array_sum(array_column($planSummary, 'amount_by_contract_slots'));

        return [
            'model' => 'plan_based',
            'reference_date' => $asOfStr,
            'closing_day' => (int) ($institution->billing_closing_day ?: 1),
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'code' => $institution->code,
            ],
            'summary' => [
                'billing_model' => 'plan_based',
                // Official invoice total (SPEC as-of): assigned employed staff × unit_price_ex_tax
                'billing_basis' => 'assigned_employed_staff × unit_price（基準日時点・公式）',
                'total_targets' => $totalAssigned,
                'total_contract_slots' => $totalContractSlots,
                'plan_count' => count($planSummary),
                'billing_amount' => $totalAmount,
                // Reference only (契約口数 × 単価) — not the official bill unless client confirms otherwise
                'contract_capacity_amount' => $totalCapacityAmount,
                'unit_price' => null,
                'return_points' => 0,
                'total_loan_points' => 0,
                'cleaning_count' => 0,
            ],
            'plan_summary' => $planSummary,
            'staff_rows' => $staffRows,
        ];
    }
}
