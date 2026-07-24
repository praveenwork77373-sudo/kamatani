<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingSnapshot;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeStatusHistory;
use App\Models\MedicalInstitution;
use App\Models\UniformProduct;
use App\Models\Department;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\View\View;
use Carbon\Carbon;

class BillingController extends Controller
{
    /**
     * Check if user has permission to manage billing (create/generate/approve)
     * Accounting managers can fully manage billing
     */
    private function checkManagePermission()
    {
        $admin = request()->user()->adminUser ?? null;
        if (!$admin || !in_array($admin->role, ['system_admin', 'operation', 'accounting'])) {
            abort(403, '請求データの管理権限がありません。');
        }
    }

    /**
     * Check if user has permission to view billing
     */
    private function checkViewPermission()
    {
        $admin = request()->user()->adminUser ?? null;
        if (!$admin || !in_array($admin->role, ['system_admin', 'operation', 'accounting', 'sales'])) {
            abort(403, '請求データの閲覧権限がありません。');
        }
    }

    public function index(Request $request): View
    {
        $this->checkViewPermission();
        // Check if billing_status column exists, create it if it doesn't
        try {
            $hasBillingStatusColumn = Schema::hasColumn('applications', 'billing_status');
            
            // If column doesn't exist, create it
            if (!$hasBillingStatusColumn) {
                try {
                    // Try using Schema builder first
                    Schema::table('applications', function (Blueprint $table) {
                        $table->enum('billing_status', ['waiting', 'approved', 'rejected'])->nullable()->default('waiting')->after('status');
                    });
                    $hasBillingStatusColumn = true;
                } catch (\Exception $e) {
                    // If Schema builder fails, try raw SQL
                    try {
                        DB::statement("ALTER TABLE `applications` ADD COLUMN `billing_status` ENUM('waiting', 'approved', 'rejected') NULL DEFAULT 'waiting' AFTER `status`");
                        $hasBillingStatusColumn = true;
                    } catch (\Exception $e2) {
                        // If both fail, continue without the column
                        $hasBillingStatusColumn = false;
                    }
                }
            }
        } catch (\Exception $e) {
            $hasBillingStatusColumn = false;
        }
        
        // Check if department_id column exists, if not try to create it
        $hasDepartmentColumn = \Illuminate\Support\Facades\Schema::hasColumn('billing_snapshots', 'department_id');
        
        if (!$hasDepartmentColumn) {
            try {
                \Illuminate\Support\Facades\Schema::table('billing_snapshots', function (Blueprint $table) {
                    $table->foreignId('department_id')->nullable()->after('medical_institution_id')->constrained('departments')->nullOnDelete();
                });
                $hasDepartmentColumn = true;
            } catch (\Exception $e) {
                // If Schema builder fails, try raw SQL
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `billing_snapshots` ADD COLUMN `department_id` BIGINT UNSIGNED NULL AFTER `medical_institution_id`");
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `billing_snapshots` ADD CONSTRAINT `billing_snapshots_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL");
                    $hasDepartmentColumn = true;
                } catch (\Exception $e2) {
                    // If both fail, continue without the column
                    \Illuminate\Support\Facades\Log::warning('Failed to create department_id column in billing_snapshots', ['error' => $e2->getMessage()]);
                    $hasDepartmentColumn = false;
                }
            }
        }
        
        // Check again after potential creation
        $hasDepartmentColumn = \Illuminate\Support\Facades\Schema::hasColumn('billing_snapshots', 'department_id');
        
        // Get auto-generated billing snapshots (created when applications were approved)
        // Now each snapshot is per department
        $query = BillingSnapshot::with(['medicalInstitution', 'generatedBy'])
            ->orderByDesc('target_date')
            ->orderByDesc('created_at');
        
        // Only eager load department if column exists
        if ($hasDepartmentColumn) {
            $query->with('department');
        }

        // Filter by institution
        $institutionId = $request->integer('medical_institution_id');
        $departmentId = $request->integer('department_id');
        
        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        // Filter by department if specified and column exists
        if ($departmentId && $hasDepartmentColumn) {
            $query->where('department_id', $departmentId);
        }

        $billingSnapshots = $query->paginate(20)
            ->withQueryString();

        // Get institutions with departments
        $institutionsWithDepartments = MedicalInstitution::with('departments')
            ->orderBy('name')
            ->get();

        // Calculate billing snapshot counts per institution and department
        $billingCounts = [
            'institution' => [],
            'department' => [],
        ];

        foreach ($institutionsWithDepartments as $institution) {
            // Count billing snapshots for this institution (sum of all department snapshots)
            $snapshotCount = BillingSnapshot::where('medical_institution_id', $institution->id)->count();

            if ($snapshotCount > 0) {
                $billingCounts['institution'][$institution->id] = $snapshotCount;
            }

            // Calculate department-level counts - now each snapshot is per department
            foreach ($institution->departments as $department) {
                // Count billing snapshots for this specific department
                $deptQuery = BillingSnapshot::where('medical_institution_id', $institution->id);
                
                // Only filter by department_id if column exists
                if (\Illuminate\Support\Facades\Schema::hasColumn('billing_snapshots', 'department_id')) {
                    $deptQuery->where('department_id', $department->id);
                } else {
                    // Fallback: filter by department in data JSON
                    $deptQuery->whereJsonContains('data->department->id', $department->id);
                }
                
                $deptCount = $deptQuery->count();
                
                if ($deptCount > 0) {
                    // Get latest snapshot for this department to get current data
                    $latestQuery = BillingSnapshot::where('medical_institution_id', $institution->id);
                    
                    // Only filter by department_id if column exists
                    if (\Illuminate\Support\Facades\Schema::hasColumn('billing_snapshots', 'department_id')) {
                        $latestQuery->where('department_id', $department->id);
                    } else {
                        // Fallback: filter by department in data JSON
                        $latestQuery->whereJsonContains('data->department->id', $department->id);
                    }
                    
                    $latestSnapshot = $latestQuery->orderByDesc('target_date')
                        ->orderByDesc('created_at')
                        ->first();
                    
                    $deptBillingAmount = 0;
                    $deptEmployeeCount = 0;
                    $deptReturnPoints = 0;
                    
                    if ($latestSnapshot) {
                        $data = $latestSnapshot->data ?? [];
                        $summary = $data['summary'] ?? [];
                        $deptBillingAmount = $summary['billing_amount'] ?? 0;
                        $deptEmployeeCount = $summary['total_targets'] ?? 0;
                        $deptReturnPoints = $summary['return_points'] ?? 0;
                    }
                    
                    $billingCounts['department'][$department->id] = [
                        'count' => $deptCount,
                        'billing_amount' => $deptBillingAmount,
                        'employee_count' => $deptEmployeeCount,
                        'return_points' => $deptReturnPoints,
                    ];
                } else {
                    // Set to 0 if no billing exists
                    $billingCounts['department'][$department->id] = [
                        'count' => 0,
                        'billing_amount' => 0,
                        'employee_count' => 0,
                        'return_points' => 0,
                    ];
                }
            }
        }

        // Calculate statistics for billing snapshots
        $totalBillingSnapshots = BillingSnapshot::count();
        
        $thisMonthBillingSnapshots = BillingSnapshot::whereBetween('target_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        ])->count();

        // Calculate total billing amount from summary data
        $totalBillingAmount = BillingSnapshot::get()->sum(function($snapshot) {
            $data = $snapshot->data ?? [];
            $summary = $data['summary'] ?? [];
            return $summary['billing_amount'] ?? 0;
        });
        
        // Calculate this month's billing amount
        $thisMonthBillingAmount = BillingSnapshot::whereBetween('target_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        ])->get()->sum(function($snapshot) {
            $data = $snapshot->data ?? [];
            $summary = $data['summary'] ?? [];
            return $summary['billing_amount'] ?? 0;
        });

        return view('admin.billing.index', [
            'billingSnapshots' => $billingSnapshots,
            'institutions' => $institutionsWithDepartments,
            'institutionsWithDepartments' => $institutionsWithDepartments,
            'billingCounts' => $billingCounts,
            'stats' => [
                'total_billing_snapshots' => $totalBillingSnapshots,
                'this_month_billing_snapshots' => $thisMonthBillingSnapshots,
                'total_billing_amount' => $totalBillingAmount,
                'this_month_billing_amount' => $thisMonthBillingAmount,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->checkManagePermission();
        
        $institutions = MedicalInstitution::orderBy('name')->get();
        $targetDate = $request->input('target_date', now()->format('Y-m-d'));
        $institutionId = $request->input('medical_institution_id');

        $contract = null;
        $previewData = null;
        
        if ($institutionId) {
            $institution = MedicalInstitution::find($institutionId);
            $contract = Contract::where('medical_institution_id', $institutionId)
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

            // Calculate preview data if contract exists
            if ($contract && $institution) {
                $previewData = $this->calculatePreviewData($institution, $targetDate, $contract);
            }
        }

        return view('admin.billing.create', [
            'institutions' => $institutions,
            'targetDate' => $targetDate,
            'selectedInstitutionId' => $institutionId,
            'contract' => $contract,
            'previewData' => $previewData,
        ]);
    }

    private function calculatePreviewData($institution, $targetDate, $contract): array
    {
        // Get employees as of target date
        $employees = Employee::where('medical_institution_id', $institution->id)->get();
        $employeesAsOfDate = [];
        
        foreach ($employees as $employee) {
            $statusAsOfDate = $employee->status;
            
            // Check status history if table exists
            if (DB::getSchemaBuilder()->hasTable('employee_status_histories')) {
                try {
                    $statusHistory = EmployeeStatusHistory::where('employee_id', $employee->id)
                        ->where('effective_from', '<=', $targetDate)
                        ->where(function($q) use ($targetDate) {
                            $q->whereNull('effective_until')
                              ->orWhere('effective_until', '>=', $targetDate);
                        })
                        ->orderByDesc('effective_from')
                        ->first();
                    
                    if ($statusHistory) {
                        $statusAsOfDate = $statusHistory->status;
                    }
                } catch (\Exception $e) {
                    // Fall back to current status
                }
            }
            
            if ($statusAsOfDate === 'employed') {
                $employeesAsOfDate[] = [
                    'id' => $employee->id,
                    'department_id' => $employee->primary_department_id,
                    'department_name' => $employee->primaryDepartment?->name ?? '未所属',
                ];
            }
        }

        // Get active loans - handle NULL dates properly
        $activeLoans = EmployeeLoan::whereHas('employee', function($q) use ($institution) {
                $q->where('medical_institution_id', $institution->id);
            })
            ->where('status', 'in_use')
            ->where(function($q) use ($targetDate) {
                // Loan is active if start date is NULL or <= target date
                // AND end date is NULL or >= target date
                $q->where(function($subQ) use ($targetDate) {
                    $subQ->whereNull('loan_start_date')
                         ->orWhere('loan_start_date', '<=', $targetDate);
                })
                ->where(function($subQ) use ($targetDate) {
                    $subQ->whereNull('loan_end_date')
                         ->orWhere('loan_end_date', '>=', $targetDate);
                });
            })
            ->get();

        // Calculate return points (総与点数) - sum of all active loan quantities
        $returnPoints = $activeLoans->sum('quantity');
        $employeesWithLoans = $activeLoans->pluck('employee_id')->unique()->count();

        // Calculate estimated billing amount
        $estimatedBilling = 0;
        if ($contract->billing_model === 'point_usage') {
            $estimatedBilling = $returnPoints * ($contract->point_unit_price ?? 0);
        } else {
            $estimatedBilling = $employeesWithLoans * ($contract->fixed_unit_price ?? 0);
        }

        // Department breakdown
        $departmentBreakdown = [];
        foreach ($employeesAsOfDate as $emp) {
            $deptId = $emp['department_id'] ?? 0;
            $deptName = $emp['department_name'];
            
            if (!isset($departmentBreakdown[$deptId])) {
                $departmentBreakdown[$deptId] = [
                    'name' => $deptName,
                    'employee_count' => 0,
                    'loan_points' => 0,
                ];
            }
            $departmentBreakdown[$deptId]['employee_count']++;
        }

        foreach ($activeLoans as $loan) {
            $emp = collect($employeesAsOfDate)->firstWhere('id', $loan->employee_id);
            if ($emp) {
                $deptId = $emp['department_id'] ?? 0;
                if (isset($departmentBreakdown[$deptId])) {
                    $departmentBreakdown[$deptId]['loan_points'] += $loan->quantity;
                }
            }
        }

        return [
            'total_employees' => count($employeesAsOfDate),
            'employees_with_loans' => $employeesWithLoans,
            'total_loan_points' => $returnPoints,
            'estimated_billing_amount' => $estimatedBilling,
            'department_breakdown' => array_values($departmentBreakdown),
        ];
    }

    public function generate(Request $request)
    {
        $this->checkManagePermission();
        
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'target_date' => ['required', 'date'],
        ]);

        $institution = MedicalInstitution::findOrFail($data['medical_institution_id']);
        $targetDate = $data['target_date'];
        $targetDateCarbon = Carbon::parse($targetDate);

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
            return back()->withErrors(['medical_institution_id' => '対象日時点で有効な契約が存在しません。']);
        }

        // Get all employees for this institution
        // We'll filter by status as of target date, not by creation date
        $allEmployees = Employee::where('medical_institution_id', $institution->id)
            ->with('primaryDepartment')
            ->get();

        // Reproduce employee status as of target date using history
        $employeesAsOfDate = [];
        
        // Check if status history table exists
        $hasStatusHistoryTable = false;
        try {
            $hasStatusHistoryTable = DB::getSchemaBuilder()->hasTable('employee_status_histories');
        } catch (\Exception $e) {
            // Table doesn't exist
        }
        
        foreach ($allEmployees as $employee) {
            $statusAsOfDate = $employee->status;
            
            if ($hasStatusHistoryTable) {
                try {
                    $statusHistory = EmployeeStatusHistory::where('employee_id', $employee->id)
                        ->where('effective_from', '<=', $targetDate)
                        ->where(function($q) use ($targetDate) {
                            $q->whereNull('effective_until')
                              ->orWhere('effective_until', '>=', $targetDate);
                        })
                        ->orderByDesc('effective_from')
                        ->first();

                    if ($statusHistory) {
                        $statusAsOfDate = $statusHistory->status;
                    }
                } catch (\Exception $e) {
                    // Fall back to current status
                }
            }

            // Only include if status was 'employed' as of target date
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

        // Get active loans as of target date with product and employee info
        // Handle loans with or without dates - if dates are NULL, consider loan always active
        $activeLoans = EmployeeLoan::with(['uniformProduct', 'employee'])
            ->whereHas('employee', function($q) use ($institution) {
                $q->where('medical_institution_id', $institution->id);
            })
            ->where('status', 'in_use')
            ->where(function($q) use ($targetDate) {
                // Loan is active if:
                // Case 1: Both dates are NULL (always active)
                // Case 2: Start date is NULL and end date is NULL or >= target date
                // Case 3: Start date <= target date and end date is NULL or >= target date
                $q->where(function($subQ) {
                    // Both dates NULL
                    $subQ->whereNull('loan_start_date')
                         ->whereNull('loan_end_date');
                })
                ->orWhere(function($subQ) use ($targetDate) {
                    // Start NULL, end valid
                    $subQ->whereNull('loan_start_date')
                         ->where(function($endQ) use ($targetDate) {
                             $endQ->whereNull('loan_end_date')
                                  ->orWhere('loan_end_date', '>=', $targetDate);
                         });
                })
                ->orWhere(function($subQ) use ($targetDate) {
                    // Start valid, end NULL or valid
                    $subQ->whereNotNull('loan_start_date')
                         ->where('loan_start_date', '<=', $targetDate)
                         ->where(function($endQ) use ($targetDate) {
                             $endQ->whereNull('loan_end_date')
                                  ->orWhere('loan_end_date', '>=', $targetDate);
                         });
                });
            })
            ->get();

        // Calculate return points (戻り点数) - items that are "in_use" and quantity >= 1
        // Based on image: "中間中"かつ"1点以上戻り中"の保管量を計上
        $returnPoints = $activeLoans->sum('quantity');
        
        // Debug logging (can be removed in production)
        if (config('app.debug')) {
            \Log::info('Billing Calculation Debug', [
                'institution_id' => $institution->id,
                'target_date' => $targetDate,
                'total_employees_found' => $allEmployees->count(),
                'employees_with_employed_status' => count($employeesAsOfDate),
                'active_loans_count' => $activeLoans->count(),
                'return_points' => $returnPoints,
                'contract_billing_model' => $contract->billing_model,
                'contract_point_unit_price' => $contract->point_unit_price,
                'contract_fixed_unit_price' => $contract->fixed_unit_price,
            ]);
        }

        // Calculate cleaning count (クリーニング回数) - based on return applications
        // Count approved return applications that were processed as of target date
        $returnApplications = \App\Models\Application::with('items')
            ->where('medical_institution_id', $institution->id)
            ->where('type', 'return')
            ->where('status', 'approved')
            ->where('approved_at', '<=', $targetDate . ' 23:59:59')
            ->where(function($q) use ($targetDate) {
                $q->whereNull('return_date')
                  ->orWhere('return_date', '<=', $targetDate);
            })
            ->get();
        
        $cleaningCount = 0;
        foreach ($returnApplications as $app) {
            $cleaningCount += $app->items()->where('action', 'return')->sum('quantity');
        }
        
        // If no return applications, use a calculation based on active loans
        // Cleaning count is typically higher than return points (multiple cleanings per item)
        // Use contract's cleaning_multiplier if available, otherwise default to 1.67
        $cleaningMultiplier = $contract->cleaning_multiplier ?? 1.67;
        if ($cleaningCount == 0) {
            $cleaningCount = (int)($returnPoints * $cleaningMultiplier);
        }

        // Group by department
        $departmentBreakdown = [];
        foreach ($employeesAsOfDate as $emp) {
            $deptId = $emp['department_id'] ?? 0;
            $deptName = $emp['department_name'];
            
            if (!isset($departmentBreakdown[$deptId])) {
                $departmentBreakdown[$deptId] = [
                    'name' => $deptName,
                    'employee_count' => 0,
                    'return_points' => 0,
                    'cleaning_count' => 0,
                    'billing_amount' => 0,
                ];
            }
            $departmentBreakdown[$deptId]['employee_count']++;
        }

        // Calculate department-level metrics from loans
        foreach ($activeLoans as $loan) {
            $emp = collect($employeesAsOfDate)->firstWhere('id', $loan->employee_id);
            if ($emp) {
                    $deptId = $emp['department_id'] ?? 0;
                if (isset($departmentBreakdown[$deptId])) {
                    $departmentBreakdown[$deptId]['return_points'] += $loan->quantity;
                    // Cleaning count per department - use contract's cleaning multiplier
                    $cleaningMultiplier = $contract->cleaning_multiplier ?? 1.67;
                    $departmentBreakdown[$deptId]['cleaning_count'] += (int)($loan->quantity * $cleaningMultiplier);
                }
            }
        }
        
        // Ensure cleaning count totals match
        $totalDeptCleaning = array_sum(array_column($departmentBreakdown, 'cleaning_count'));
        if ($totalDeptCleaning > 0 && abs($totalDeptCleaning - $cleaningCount) > 10) {
            // Adjust proportionally
            $ratio = $cleaningCount / $totalDeptCleaning;
            foreach ($departmentBreakdown as $deptId => &$dept) {
                $dept['cleaning_count'] = (int)($dept['cleaning_count'] * $ratio);
            }
        }

        // Calculate billing per department based on contract model
        foreach ($departmentBreakdown as $deptId => &$dept) {
            if ($contract->billing_model === 'point_usage') {
                $pointUnitPrice = $contract->point_unit_price ?? 0;
                $dept['billing_amount'] = $dept['return_points'] * $pointUnitPrice;
            } else {
                // Fixed per person: use employee count in department
                $fixedUnitPrice = $contract->fixed_unit_price ?? 0;
                $dept['billing_amount'] = $dept['employee_count'] * $fixedUnitPrice;
            }
        }

        // Group by item category with sub-categories
        $itemBreakdown = [];
        foreach ($activeLoans as $loan) {
            $product = $loan->uniformProduct;
            if (!$product) continue;

            $category = $product->category;
            $gender = $product->gender;

            // Map category to display name
            $categoryMap = [
                'top' => '白衣 (上着)',
                'bottom' => 'パンツ (ズボン)',
                'shoe' => 'シューズ',
                'other' => 'その他',
            ];
            $categoryName = $categoryMap[$category] ?? $category;

            // Map gender to display name
            $genderMap = [
                'male' => '男性用',
                'female' => '女性用',
                'unisex' => 'ユニセックス',
            ];
            $genderName = $genderMap[$gender] ?? $gender;

            // For shoes, use type instead of gender
            if ($category === 'shoe') {
                // Determine shoe type from product name
                $productNameLower = strtolower($product->name);
                if (str_contains($productNameLower, 'sneaker') || str_contains($productNameLower, 'スニーカー')) {
                    $subCategoryKey = 'スニーカータイプ';
                } elseif (str_contains($productNameLower, 'sandal') || str_contains($productNameLower, 'サンダル')) {
                    $subCategoryKey = 'サンダルタイプ';
                } else {
                    // Default to sneaker if unclear
                    $subCategoryKey = 'スニーカータイプ';
                }
            } elseif ($category === 'other') {
                // For other category, determine sub-type from name
                $productNameLower = strtolower($product->name);
                if (str_contains($productNameLower, 'apron') || str_contains($productNameLower, 'エプロン')) {
                    $subCategoryKey = 'エプロン';
                } elseif (str_contains($productNameLower, 'cap') || str_contains($productNameLower, 'キャップ') || str_contains($productNameLower, '帽子')) {
                    $subCategoryKey = 'キャップ';
                } else {
                    $subCategoryKey = 'その他';
                }
            } else {
                $subCategoryKey = $genderName;
            }

            if (!isset($itemBreakdown[$category])) {
                $itemBreakdown[$category] = [
                    'name' => $categoryName,
                    'total_points' => 0,
                    'sub_categories' => [],
                ];
            }

            $itemBreakdown[$category]['total_points'] += $loan->quantity;

            if (!isset($itemBreakdown[$category]['sub_categories'][$subCategoryKey])) {
                $itemBreakdown[$category]['sub_categories'][$subCategoryKey] = 0;
            }
            $itemBreakdown[$category]['sub_categories'][$subCategoryKey] += $loan->quantity;
        }

        // Calculate billing amount based on contract model
        $billingAmount = 0;
        if ($contract->billing_model === 'point_usage') {
            // Point-based: return points × point unit price
            $pointUnitPrice = $contract->point_unit_price ?? 0;
            $billingAmount = $returnPoints * $pointUnitPrice;
        } else {
            // Fixed per person: count employees who have active loans
            // If billing model is fixed_per_person, we bill per employee who has loans
            $employeesWithLoans = $activeLoans->pluck('employee_id')->unique()->count();
            // Fallback: if no loans but we have employees, use total employees
            // (This handles the case where billing is per employee regardless of loans)
            if ($employeesWithLoans == 0 && count($employeesAsOfDate) > 0) {
                $employeesWithLoans = count($employeesAsOfDate);
            }
            $fixedUnitPrice = $contract->fixed_unit_price ?? 0;
            $billingAmount = $employeesWithLoans * $fixedUnitPrice;
        }
        
        // Update department billing amounts to match total
        $totalDeptBilling = array_sum(array_column($departmentBreakdown, 'billing_amount'));
        if ($totalDeptBilling > 0 && abs($totalDeptBilling - $billingAmount) > 1) {
            // Adjust proportionally if there's a difference
            $ratio = $billingAmount / $totalDeptBilling;
            foreach ($departmentBreakdown as $deptId => &$dept) {
                $dept['billing_amount'] = (int)($dept['billing_amount'] * $ratio);
            }
        }

        // Generate invoice number (format: INV-YYYY-MM-####)
        $monthStart = $targetDateCarbon->copy()->startOfMonth();
        $monthEnd = $targetDateCarbon->copy()->endOfMonth();
        $invoiceCount = BillingSnapshot::where('medical_institution_id', $institution->id)
            ->whereBetween('target_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->count() + 1;
        $invoiceNumber = 'INV-' . $targetDateCarbon->format('Y-m') . '-' . str_pad($invoiceCount, 4, '0', STR_PAD_LEFT);

        $billingData = [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'code' => $institution->code,
            ],
            'target_date' => $targetDate,
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
                'total_targets' => count($employeesAsOfDate),
                'total_loan_points' => $returnPoints, // 総与点数 (same as return points for now)
                'return_points' => $returnPoints,
                'cleaning_count' => $cleaningCount,
                'billing_amount' => $billingAmount,
                'employees_with_loans' => $activeLoans->pluck('employee_id')->unique()->count(),
                'unit_price' => $contract->fixed_unit_price ?? $contract->point_unit_price ?? 0,
            ],
            'department_breakdown' => array_values($departmentBreakdown),
            'item_breakdown' => array_values($itemBreakdown),
            'invoice' => [
                'invoice_number' => $invoiceNumber,
                'issue_date' => now()->format('Y年n月j日'),
                'payment_due_date' => now()->addMonth()->format('Y年n月j日'),
                'created_at_formatted' => now()->format('Y年n月j日 G時i分'),
                'subtotal' => $billingAmount,
                'tax_rate' => 10,
                'tax_amount' => (int)floor($billingAmount * 0.1),
                'total_amount' => $billingAmount + (int)floor($billingAmount * 0.1),
            ],
        ];

        $snapshot = BillingSnapshot::create([
            'medical_institution_id' => $institution->id,
            'target_date' => $targetDate,
            'data' => $billingData,
            'generated_by_admin_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.billing.show', $snapshot)
            ->with('status', '請求データを生成しました。');
    }

    public function show(BillingSnapshot $snapshot): View
    {
        $this->checkViewPermission();
        
        $snapshot->load(['medicalInstitution', 'generatedBy']);
        return view('admin.billing.show', ['snapshot' => $snapshot]);
    }

    /**
     * Approve billing for an application
     * 
     * Note: This method is used to manually approve billing for an application.
     * When an application is first approved, billing is auto-generated with billing_status = 'waiting'.
     * This method sets billing_status = 'approved' and regenerates/updates the billing snapshot
     * to ensure it includes the latest data.
     */
    public function approve(Request $request, int $applicationId): RedirectResponse
    {
        /** @var \App\Models\AdminUser|null $admin */
        $admin = $request->user()->adminUser;

        if (!$admin || !in_array($admin->role, ['system_admin', 'operation', 'accounting'])) {
            abort(403, '請求承認権限がありません。');
        }

        $application = Application::findOrFail($applicationId);

        if ($application->status !== 'approved') {
            return back()->with('error', 'この申請は承認されていません。請求承認は承認済みの申請にのみ可能です。');
        }

        // Check if billing_status column exists
        $hasBillingStatusColumn = Schema::hasColumn('applications', 'billing_status');
        
        if ($hasBillingStatusColumn && $application->billing_status === 'approved') {
            return back()->with('error', 'この申請の請求は既に承認済みです。');
        }

        DB::transaction(function () use ($application, $request, $hasBillingStatusColumn) {
            // Set billing status to approved if column exists
            if ($hasBillingStatusColumn) {
                $application->billing_status = 'approved';
                $application->save();
            }

            // Regenerate/update billing snapshot to ensure it includes latest data
            // This will update existing snapshot or create new one if needed
            \App\Services\AutoBillingService::generateBillingForApprovedApplication($application, $request->user()->id);
        });

        return back()->with('status', '請求を承認しました。請求データを更新しました。');
    }
}
