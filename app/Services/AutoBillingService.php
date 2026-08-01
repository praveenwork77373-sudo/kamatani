<?php

namespace App\Services;

use App\Models\Application;
use App\Models\BillingSnapshot;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeStatusHistory;
use App\Models\MedicalInstitution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated SPEC 2026-07-31: FIXED/POINT application-triggered billing is superseded by
 * PlanBillingService (plan × assigned employed staff × unit price as-of closing day).
 * Kept for reference only — do not call from application accept / billing approve.
 */
class AutoBillingService
{
    /**
     * Automatically generate billing for approved application
     */
    public static function generateBillingForApprovedApplication(Application $application, int $userId): void
    {
        try {
            Log::info('Auto billing generation started', [
                'application_id' => $application->id,
                'institution_id' => $application->medical_institution_id,
            ]);

            $institution = $application->medicalInstitution;
            if (!$institution) {
                Log::warning('Auto billing skipped: No institution found', [
                    'application_id' => $application->id,
                ]);
                return;
            }

            // Use current month as target date for billing
            $targetDate = now()->format('Y-m-d');
            $targetDateCarbon = Carbon::parse($targetDate);
            $monthStart = $targetDateCarbon->copy()->startOfMonth();
            $monthEnd = $targetDateCarbon->copy()->endOfMonth();

            // Get contract active as of target date
            $contract = Contract::where('medical_institution_id', $institution->id)
                ->where(function($q) use ($targetDate) {
                    $q->whereNull('valid_from')
                      ->orWhere('valid_from', '<=', $targetDate);
                })
                ->where(function($q) use ($targetDate) {
                    $q->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', $targetDate);
                })
                ->orderByDesc('valid_from')
                ->first();

            if (!$contract) {
                // No active contract, skip billing generation
                Log::warning('Auto billing skipped: No active contract found', [
                    'application_id' => $application->id,
                    'institution_id' => $institution->id,
                    'target_date' => $targetDate,
                ]);
                return;
            }

            // Get the department from the application
            $department = $application->department;
            if (!$department) {
                Log::warning('Auto billing skipped: No department found', [
                    'application_id' => $application->id,
                    'department_id' => $application->department_id,
                ]);
                return;
            }

            // Check if billing already exists for this department and month
            // If it exists, we should update it to include the new application
            $existingBilling = BillingSnapshot::where('medical_institution_id', $institution->id)
                ->where('department_id', $department->id)
                ->whereBetween('target_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                ->first();

            if ($existingBilling) {
                // Billing already exists for this department and month, update it to include new application
                Log::info('Auto billing: Updating existing billing snapshot', [
                    'application_id' => $application->id,
                    'institution_id' => $institution->id,
                    'department_id' => $department->id,
                    'existing_billing_id' => $existingBilling->id,
                    'target_date' => $targetDate,
                ]);
                
                // Regenerate billing snapshot to include new application
                self::generateBillingSnapshot($institution, $department, $targetDate, $contract, $userId, $existingBilling);
                return;
            }

            Log::info('Auto billing: Generating snapshot', [
                'application_id' => $application->id,
                'institution_id' => $institution->id,
                'department_id' => $department->id,
                'contract_id' => $contract->id,
                'target_date' => $targetDate,
            ]);

            // Generate billing snapshot for this department
            self::generateBillingSnapshot($institution, $department, $targetDate, $contract, $userId);
            
            Log::info('Auto billing generation completed successfully', [
                'application_id' => $application->id,
                'institution_id' => $institution->id,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the approval
            Log::error('Auto billing generation failed', [
                'application_id' => $application->id,
                'institution_id' => $application->medical_institution_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // 承認を失敗させない（ログのみ）
        }
    }

    /**
     * 部署ごとの請求スナップショットを生成する。
     * 基準日（対象月1日）時点の在籍・貸出状態で計算する。
     * 定額（人数×単価）またはポイント制（返却ポイント×単価）。
     */
    private static function generateBillingSnapshot(MedicalInstitution $institution, Department $department, string $targetDate, Contract $contract, int $userId, ?BillingSnapshot $existingSnapshot = null): void
    {
        $targetDateCarbon = Carbon::parse($targetDate);
        
        // 対象月1日を基準日として在籍・貸出状態を再現する
        $referenceDate = $targetDateCarbon->copy()->startOfMonth()->format('Y-m-d');
        $referenceDateCarbon = Carbon::parse($referenceDate);
        $monthStart = $targetDateCarbon->copy()->startOfMonth();
        $monthEnd = $targetDateCarbon->copy()->endOfMonth();
        
        Log::info('Auto billing: Using reference date for billing calculation', [
            'institution_id' => $institution->id,
            'department_id' => $department->id,
            'target_date' => $targetDate,
            'reference_date' => $referenceDate,
        ]);

        // Get all employees for this institution and department
        $allEmployees = Employee::where('medical_institution_id', $institution->id)
            ->where('primary_department_id', $department->id)
            ->with('primaryDepartment')
            ->get();

        // 基準日時点の職員状態を履歴から再現
        $employeesAsOfDate = [];
        $hasStatusHistoryTable = false;
        try {
            $hasStatusHistoryTable = DB::getSchemaBuilder()->hasTable('employee_status_histories');
        } catch (\Exception $e) {
            // Table doesn't exist
        }
        
        foreach ($allEmployees as $employee) {
            // Check employment status as of reference date (1st of month)
            $statusAsOfDate = $employee->status;
            
            if ($hasStatusHistoryTable) {
                try {
                    // Find the status that was effective on the reference date
                    $statusHistory = EmployeeStatusHistory::where('employee_id', $employee->id)
                        ->where('effective_from', '<=', $referenceDate)
                        ->where(function($q) use ($referenceDate) {
                            $q->whereNull('effective_until')
                              ->orWhere('effective_until', '>=', $referenceDate);
                        })
                        ->orderByDesc('effective_from')
                        ->first();

                    if ($statusHistory) {
                        $statusAsOfDate = $statusHistory->status;
                    }
                } catch (\Exception $e) {
                    // Fall back to current status
                    Log::warning('Auto billing: Error checking employee status history', [
                        'employee_id' => $employee->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Only include employees who were "employed" as of the reference date
            if ($statusAsOfDate === 'employed') {
                $employeesAsOfDate[] = [
                    'id' => $employee->id,
                    'staff_no' => $employee->staff_no,
                    'name' => $employee->name,
                    'status' => $statusAsOfDate,
                    'department_id' => $employee->primary_department_id,
                    'department_name' => $employee->primaryDepartment?->name ?? '未所属',
                ];
            }
        }
        
        Log::info('Auto billing: Employees as of reference date', [
            'institution_id' => $institution->id,
            'reference_date' => $referenceDate,
            'total_employees' => count($employeesAsOfDate),
        ]);

        // Get approved applications for this institution and department (for tracking)
        // Include all applications approved up to the end of the target month
        // This is used for item breakdown and tracking purposes
        $approvedApplications = Application::with(['items.uniformProduct', 'employee', 'department'])
            ->where('medical_institution_id', $institution->id)
            ->where('department_id', $department->id)
            ->whereIn('status', [\App\Models\Application::STATUS_IN_PROGRESS, \App\Models\Application::STATUS_COMPLETED, 'approved'])
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', $monthEnd->format('Y-m-d') . ' 23:59:59')
            ->get();
        
        Log::info('Auto billing: Approved applications found', [
            'institution_id' => $institution->id,
            'target_date' => $targetDate,
            'applications_count' => $approvedApplications->count(),
            'application_ids' => $approvedApplications->pluck('id')->toArray(),
        ]);

        // Get loans active at any point during the target month (includes mid-month approvals)
        $activeLoans = EmployeeLoan::with(['uniformProduct', 'employee', 'application'])
            ->whereHas('employee', function($q) use ($institution, $department) {
                $q->where('medical_institution_id', $institution->id)
                  ->where('primary_department_id', $department->id);
            })
            ->where('status', 'in_use')
            ->where(function($q) use ($monthStart, $monthEnd) {
                $monthStartStr = $monthStart->format('Y-m-d');
                $monthEndStr = $monthEnd->format('Y-m-d');
                $q->where(function($subQ) use ($monthEndStr) {
                    $subQ->whereNull('loan_start_date')
                         ->orWhere('loan_start_date', '<=', $monthEndStr);
                })
                ->where(function($subQ) use ($monthStartStr) {
                    $subQ->whereNull('loan_end_date')
                         ->orWhere('loan_end_date', '>=', $monthStartStr);
                });
            })
            ->get();

        // Calculate return points (総与点数) based on contract limits
        // Japan rental system: Only count items within contract limits
        // Items exceeding limits are only counted if they have special approval
        $returnPoints = 0;
        $perPersonLimit = $contract->per_person_loan_limit ?? null;
        $perItemLimit = $contract->loan_limit_per_item ?? null;
        
        // Filter loans to only include employees who are employed as of reference date
        $validLoans = $activeLoans->filter(function($loan) use ($employeesAsOfDate) {
            return collect($employeesAsOfDate)->contains('id', $loan->employee_id);
        });
        
        // Group loans by employee to check per-person limits
        $loansByEmployee = $validLoans->groupBy('employee_id');
        
        foreach ($loansByEmployee as $employeeId => $employeeLoans) {
            $employeeTotalQuantity = $employeeLoans->sum('quantity');
            $hasSpecialApproval = false;
            
            // Check if any loan from this employee has special approval
            foreach ($employeeLoans as $loan) {
                if ($loan->application && $loan->application->special_approval_reason) {
                    $hasSpecialApproval = true;
                    break;
                }
            }
            
            // Calculate points for this employee
            $employeePoints = 0;
            
            if ($perPersonLimit === null || $employeeTotalQuantity <= $perPersonLimit || $hasSpecialApproval) {
                // No per-person limit OR within limit OR has special approval
                // Count all items, but check per-item limits
                foreach ($employeeLoans as $loan) {
                    $itemQuantity = $loan->quantity;
                    $loanHasSpecialApproval = $loan->application && $loan->application->special_approval_reason;
                    
                    if ($perItemLimit === null || $itemQuantity <= $perItemLimit || $loanHasSpecialApproval) {
                        // No per-item limit OR within limit OR has special approval
                        $employeePoints += $itemQuantity;
                    } else {
                        // Exceeds per-item limit and no special approval - only count up to limit
                        $employeePoints += $perItemLimit;
                    }
                }
            } else {
                // Exceeds per-person limit and no special approval
                // Only count up to per-person limit, distributed proportionally
                $proportion = $perPersonLimit / max($employeeTotalQuantity, 1);
                
                foreach ($employeeLoans as $loan) {
                    $itemQuantity = $loan->quantity;
                    $loanHasSpecialApproval = $loan->application && $loan->application->special_approval_reason;
                    
                    if ($loanHasSpecialApproval) {
                        // Special approval - count full quantity
                        $employeePoints += $itemQuantity;
                    } else {
                        // Apply proportion, but also check per-item limit
                        $proportionalQuantity = (int)($itemQuantity * $proportion);
                        if ($perItemLimit !== null && $proportionalQuantity > $perItemLimit) {
                            $proportionalQuantity = $perItemLimit;
                        }
                        $employeePoints += $proportionalQuantity;
                    }
                }
            }
            
            $returnPoints += $employeePoints;
        }
        
        Log::info('Auto billing: Active loans as of reference date', [
            'institution_id' => $institution->id,
            'department_id' => $department->id,
            'reference_date' => $referenceDate,
            'total_loans' => $activeLoans->count(),
            'return_points' => $returnPoints,
            'per_person_limit' => $contract->per_person_loan_limit,
            'per_item_limit' => $contract->loan_limit_per_item,
            'contract_billing_model' => $contract->billing_model,
        ]);
        
        // Calculate cleaning count for the target month only
        $returnApplications = Application::with('items')
            ->where('medical_institution_id', $institution->id)
            ->where('department_id', $department->id)
            ->where('type', 'return')
            ->whereIn('status', [\App\Models\Application::STATUS_IN_PROGRESS, \App\Models\Application::STATUS_COMPLETED, 'approved'])
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [
                $monthStart->format('Y-m-d') . ' 00:00:00',
                $monthEnd->format('Y-m-d') . ' 23:59:59',
            ])
            ->where(function($q) use ($monthStart, $monthEnd) {
                $q->whereNull('return_date')
                  ->orWhereBetween('return_date', [
                      $monthStart->format('Y-m-d'),
                      $monthEnd->format('Y-m-d'),
                  ]);
            })
            ->get();
        
        // Sum up quantities from return application items
        $cleaningCount = 0;
        foreach ($returnApplications as $app) {
            $returnItems = $app->items()->where('action', 'return')->get();
            foreach ($returnItems as $item) {
                $cleaningCount += $item->quantity;
            }
        }
        
        // If no actual return applications exist, estimate cleaning cycles from active loans
        $cleaningMultiplier = $contract->cleaning_multiplier ?? 1.67;
        if ($cleaningCount == 0 && $returnPoints > 0) {
            $cleaningCount = (int)($returnPoints * $cleaningMultiplier);
        }
        
        Log::info('Auto billing: Cleaning cycles calculated', [
            'institution_id' => $institution->id,
            'reference_date' => $referenceDate,
            'cleaning_count' => $cleaningCount,
            'return_points' => $returnPoints,
            'cleaning_multiplier' => $cleaningMultiplier,
        ]);

        // Since we're creating billing for a single department, create a single department breakdown entry
        $departmentBreakdown = [];
        $deptId = $department->id;
        $deptName = $department->name;
        
        $departmentBreakdown[$deptId] = [
            'id' => $deptId,
            'name' => $deptName,
            'employee_count' => count($employeesAsOfDate),
            'return_points' => $returnPoints,
            'cleaning_count' => $cleaningCount,
            'billing_amount' => 0,
        ];

        // Calculate billing for this department based on contract billing model
        // fixed_per_person: 在籍者数 × 固定単価
        // point_usage: 総与点数 × ポイント単価
        if ($contract->billing_model === 'point_usage') {
            $pointUnitPrice = $contract->point_unit_price ?? 0;
            $departmentBreakdown[$deptId]['billing_amount'] = (int)($returnPoints * $pointUnitPrice);
        } else {
            $fixedUnitPrice = $contract->fixed_unit_price ?? 0;
            $departmentBreakdown[$deptId]['billing_amount'] = (int)(count($employeesAsOfDate) * $fixedUnitPrice);
        }

        $billingAmount = $departmentBreakdown[$deptId]['billing_amount'];
        
        Log::info('Auto billing: Billing calculation', [
            'institution_id' => $institution->id,
            'department_id' => $department->id,
            'billing_model' => $contract->billing_model,
            'billing_amount' => $billingAmount,
        ]);

        // Generate or reuse invoice number
        if ($existingSnapshot && !empty($existingSnapshot->data['invoice']['invoice_number'])) {
            $invoiceNumber = $existingSnapshot->data['invoice']['invoice_number'];
        } else {
            $invoiceCount = BillingSnapshot::where('medical_institution_id', $institution->id)
                ->where('department_id', $department->id)
                ->whereBetween('target_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                ->when($existingSnapshot, fn ($q) => $q->where('id', '!=', $existingSnapshot->id))
                ->count() + 1;
            $invoiceNumber = 'INV-' . $targetDateCarbon->format('Y-m') . '-' . str_pad($department->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($invoiceCount, 4, '0', STR_PAD_LEFT);
        }

        // Build item breakdown from approved applications
        $itemBreakdown = [];
        foreach ($approvedApplications as $app) {
            // Ensure items are loaded
            if (!$app->relationLoaded('items')) {
                $app->load('items.uniformProduct');
            }
            
            foreach ($app->items as $item) {
                // Ensure uniformProduct is loaded
                if (!$item->relationLoaded('uniformProduct')) {
                    $item->load('uniformProduct');
                }
                
                $product = $item->uniformProduct;
                if (!$product) {
                    Log::warning('Auto billing: Product not found for application item', [
                        'application_id' => $app->id,
                        'item_id' => $item->id,
                        'uniform_product_id' => $item->uniform_product_id,
                    ]);
                    continue;
                }
                
                $key = $product->id . '_' . ($product->size ?? 'default');
                if (!isset($itemBreakdown[$key])) {
                    $itemBreakdown[$key] = [
                        'product_id' => $product->id,
                        'product_code' => $product->product_code ?? '',
                        'product_name' => $product->name ?? '',
                        'size' => $product->size ?? '',
                        'category' => $product->category ?? '',
                        'gender' => $product->gender ?? '',
                        'quantity' => 0,
                        'applications' => [],
                    ];
                }
                $itemBreakdown[$key]['quantity'] += $item->quantity;
                $itemBreakdown[$key]['applications'][] = [
                    'application_id' => $app->id,
                    'employee_id' => $app->employee_id,
                    'employee_name' => $app->employee?->name ?? '',
                    'employee_staff_no' => $app->employee?->staff_no ?? '',
                    'department_id' => $app->department_id,
                    'department_name' => $app->department?->name ?? '',
                    'quantity' => $item->quantity,
                    'approved_at' => $app->approved_at?->format('Y-m-d H:i:s') ?? '',
                ];
            }
        }
        
        Log::info('Auto billing: Item breakdown created', [
            'institution_id' => $institution->id,
            'target_date' => $targetDate,
            'item_count' => count($itemBreakdown),
            'item_keys' => array_keys($itemBreakdown),
        ]);

        $billingData = [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'code' => $institution->code,
            ],
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
            'target_date' => $targetDate,
            'reference_date' => $referenceDate, // Date used for status reproduction (1st of month)
            'target_month' => $targetDateCarbon->format('Y年n月'),
            'contract' => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number ?? 'CT-' . $targetDateCarbon->format('Y') . '-' . str_pad($contract->id, 4, '0', STR_PAD_LEFT),
                'contract_type' => $contract->contract_type,
                'billing_model' => $contract->billing_model,
                'per_person_loan_limit' => $contract->per_person_loan_limit,
                'loan_limit_per_item' => $contract->loan_limit_per_item,
                'point_unit_price' => $contract->point_unit_price,
                'fixed_unit_price' => $contract->fixed_unit_price,
                'valid_from' => $contract->valid_from?->format('Y/m/d'),
                'valid_until' => $contract->valid_until?->format('Y/m/d'),
            ],
            'summary' => [
                'total_targets' => count($employeesAsOfDate), // 対象員数 (在籍者数)
                'total_loan_points' => $returnPoints, // 総与点数 (貸与中のアイテム総数)
                'return_points' => $returnPoints, // Same as total_loan_points
                'cleaning_count' => $cleaningCount, // クリーニング回数
                'billing_amount' => $billingAmount, // 請求金額 (税抜)
                'employees_with_loans' => $activeLoans->pluck('employee_id')->unique()->count(), // 貸与中の従業員数
                'unit_price' => $contract->billing_model === 'point_usage' 
                    ? ($contract->point_unit_price ?? 0) 
                    : ($contract->fixed_unit_price ?? 0), // 単価 (ポイント単価 or 固定単価)
                'billing_model' => $contract->billing_model, // 請求モデル
                'total_applications' => $approvedApplications->count(), // 対象申請数
                'applications_included' => $approvedApplications->pluck('id')->toArray(), // 含まれる申請ID
            ],
            'department_breakdown' => array_values($departmentBreakdown), // Single department entry
            'item_breakdown' => array_values($itemBreakdown),
            'applications' => $approvedApplications->map(function($app) {
                return [
                    'id' => $app->id,
                    'type' => $app->type,
                    'employee_id' => $app->employee_id,
                    'employee_name' => $app->employee?->name,
                    'employee_staff_no' => $app->employee?->staff_no,
                    'department_id' => $app->department_id,
                    'department_name' => $app->department?->name,
                    'start_date' => $app->start_date?->format('Y-m-d'),
                    'end_date' => $app->end_date?->format('Y-m-d'),
                    'approved_at' => $app->approved_at?->format('Y-m-d H:i:s'),
                    'items' => $app->items->map(function($item) {
                        return [
                            'product_id' => $item->uniform_product_id,
                            'product_name' => $item->uniformProduct?->name,
                            'quantity' => $item->quantity,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            'invoice' => [
                'invoice_number' => $invoiceNumber,
                'issue_date' => now()->format('Y年n月j日'), // 発行日
                'payment_due_date' => now()->addMonth()->format('Y年n月j日'), // 支払期限
                'created_at_formatted' => now()->format('Y年n月j日 G時i分'), // 作成日時
                'subtotal' => $billingAmount, // 小計 (税抜)
                'tax_rate' => 10, // 消費税率 (%)
                'tax_amount' => (int)floor($billingAmount * 0.1), // 消費税額
                'total_amount' => $billingAmount + (int)floor($billingAmount * 0.1), // 合計金額 (税込)
            ],
        ];

        if ($existingSnapshot) {
            // Update existing snapshot
            $existingSnapshot->update([
                'data' => $billingData,
                'generated_by_admin_user_id' => $userId,
            ]);
            $snapshot = $existingSnapshot;
        } else {
            // Create new snapshot for this department
            $snapshot = BillingSnapshot::create([
                'medical_institution_id' => $institution->id,
                'department_id' => $department->id,
                'target_date' => $targetDate,
                'data' => $billingData,
                'generated_by_admin_user_id' => $userId,
            ]);
        }
        
        Log::info('Billing snapshot created successfully', [
            'snapshot_id' => $snapshot->id,
            'institution_id' => $institution->id,
            'department_id' => $department->id,
            'target_date' => $targetDate,
            'billing_amount' => $billingAmount,
        ]);
    }
}

