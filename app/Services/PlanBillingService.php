<?php

namespace App\Services;

use App\Models\CustomerContractPlan;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\MedicalInstitution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Monthly plan-based billing (SPEC / 日本語仕様 4.3 + 経理処理の流れ).
 *
 * Two different “dates” — do not mix them:
 * 1) Hospital 締め日 (billing_closing_day): monthly snapshot reference day (e.g. 1st).
 * 2) Customer contract plan start_date / end_date: when that hospital–plan contract is in force.
 *
 * Each month Kamatani generates as-of the hospital 締め日:
 * - Include only employed staff
 * - With an active employee↔plan assignment on that day
 * - Whose hospital customer-contract-plan is confirmed and within start/end on that day
 * Official amount = assigned headcount × plan unit_price_ex_tax (matches client Excel 対象人数).
 * 契約口数 (contract_count) is contracted capacity — shown as reference, not the invoice total
 * unless the client later confirms otherwise (msg.txt wording).
 */
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
        $planAgg = []; // key: customer_contract_plan_id

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
                // One billing unit = one employed person assigned to this plan as of 基準日
                $headcountUnit = 1;
                $amount = $headcountUnit * $unitPrice;

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
                    'contract_start_date' => optional($ccp->start_date)->format('Y-m-d'),
                    'contract_end_date' => optional($ccp->end_date)->format('Y-m-d'),
                    'contracted_slots' => (int) $ccp->contract_count,
                    'contract_count' => $headcountUnit, // SPEC output field: units billed for this staff row
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
                        'contract_start_date' => optional($ccp->start_date)->format('Y-m-d'),
                        'contract_end_date' => optional($ccp->end_date)->format('Y-m-d'),
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

        // Include confirmed plans active on as-of with zero assignment (capacity still visible)
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
                'contract_start_date' => optional($ccp->start_date)->format('Y-m-d'),
                'contract_end_date' => optional($ccp->end_date)->format('Y-m-d'),
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
            'billing_month' => $asOf->format('Y-m'),
            'closing_day' => (int) ($institution->billing_closing_day ?: 1),
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'code' => $institution->code,
            ],
            'summary' => [
                'billing_model' => 'plan_based',
                // Official invoice total: assigned employed staff × unit_price as of 基準日
                'billing_basis' => 'assigned_employed_staff × unit_price（基準日時点・公式）',
                'billing_basis_note' => '毎月、医療機関の締め日を基準日としてスナップショットします。顧客契約プランの終了日まで「一括請求」するのではなく、契約有効期間中の各月で割当在籍人数×単価を計算します。',
                'total_targets' => $totalAssigned,
                'total_assigned' => $totalAssigned,
                'total_contract_slots' => $totalContractSlots,
                'plan_count' => count($planSummary),
                'billing_amount' => $totalAmount,
                'total_amount_ex_tax' => $totalAmount,
                // Reference only (契約口数 × 単価)
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
