<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Application;
use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\EmployeeLoan;
use App\Models\MedicalInstitution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        /** @var CustomerUser|null $customer */
        $customer = $user->customerUser;

        /** @var AdminUser|null $admin */
        $admin = $user->adminUser;

        $stats = [];
        $recentApplications = collect();
        $departmentStats = collect();
        $medicalInstitution = null;
        $closingDay = null;
        
        if ($admin) {
            $opsRoles = ['system_admin', 'operation'];
            $canSeeOps = in_array($admin->role, $opsRoles, true);

            if ($canSeeOps) {
                // Kamatani ops dashboard
                $stats = [
                    'total_applications' => Application::count(),
                    'pending_approvals' => Application::whereIn('status', ['pending', 'waiting_special_approval'])->count(),
                    'total_employees' => Employee::count(),
                    'inventory_alerts' => \App\Models\UniformProduct::where('stock_quantity', '<=', 0)->count(),
                ];

                $recentApplications = Application::with(['employee', 'department', 'items.uniformProduct', 'medicalInstitution'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                $queueApplications = Application::with(['employee', 'department', 'medicalInstitution'])
                    ->whereIn('status', ['pending', 'waiting_special_approval', 'cancel_request'])
                    ->orderByRaw("CASE status WHEN 'waiting_special_approval' THEN 0 WHEN 'cancel_request' THEN 1 ELSE 2 END")
                    ->orderBy('created_at')
                    ->limit(20)
                    ->get();

                $queueCounts = [
                    'pending' => Application::where('status', 'pending')->count(),
                    'special' => Application::where('status', 'waiting_special_approval')->count(),
                    'cancel' => Application::where('status', 'cancel_request')->count(),
                ];
                $queueCounts['total'] = $queueCounts['pending'] + $queueCounts['special'] + $queueCounts['cancel'];

                $trendData = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = now()->subMonths($i);
                    $monthStart = $month->copy()->startOfMonth();
                    $monthEnd = $month->copy()->endOfMonth();

                    $trendData[] = [
                        'month' => $month->format(app()->getLocale() === 'ja' ? 'n月' : 'M'),
                        'month_full' => $month->format(app()->getLocale() === 'ja' ? 'Y年n月' : 'M Y'),
                        'new_loan' => Application::where('type', 'new_loan')
                            ->whereBetween('created_at', [$monthStart, $monthEnd])
                            ->count(),
                        'return' => Application::where('type', 'return')
                            ->whereBetween('created_at', [$monthStart, $monthEnd])
                            ->count(),
                        'resize' => Application::where('type', 'resize')
                            ->whereBetween('created_at', [$monthStart, $monthEnd])
                            ->count(),
                        'exchange' => Application::where('type', 'exchange')
                            ->whereBetween('created_at', [$monthStart, $monthEnd])
                            ->count(),
                    ];
                }

                $departmentStats = Department::withCount(['employees', 'applications'])
                    ->limit(6)
                    ->get();
            } else {
                // Non-ops admin roles: no application queue / inventory ops leakage
                $stats = [
                    'total_applications' => null,
                    'pending_approvals' => null,
                    'total_employees' => in_array($admin->role, ['system_admin', 'sales', 'accounting'], true)
                        ? Employee::count()
                        : null,
                    'inventory_alerts' => null,
                    'restricted_dashboard' => true,
                ];
                $recentApplications = collect();
                $queueApplications = collect();
                $queueCounts = ['pending' => 0, 'special' => 0, 'cancel' => 0, 'total' => 0];
                $trendData = [];
                $departmentStats = collect();
            }
        } elseif ($customer) {
            // Customer (Medical Institution) Dashboard
            $medicalInstitution = $customer->medicalInstitution;
            $closingDay = $medicalInstitution?->billing_closing_day ?? 1;
            
            $baseQuery = Application::where('medical_institution_id', $customer->medical_institution_id);
            
            if ($customer->isDepartmentAdmin()) {
                if (! $customer->department_id) {
                    $baseQuery->whereRaw('0 = 1');
                } else {
                    $baseQuery->where('department_id', $customer->department_id);
                }
            }
            
            // Employee stats
            $employeeQuery = Employee::where('medical_institution_id', $customer->medical_institution_id);
            if ($customer->isDepartmentAdmin()) {
                if (! $customer->department_id) {
                    $employeeQuery->whereRaw('0 = 1');
                } else {
                    $employeeQuery->inDepartment((int) $customer->department_id);
                }
            }
            
            $totalEmployees = $employeeQuery->count();
            $employedEmployees = (clone $employeeQuery)->where('status', 'employed')->count();
            $newEmployeesThisMonth = (clone $employeeQuery)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
            
            // Loan stats
            $activeLoans = EmployeeLoan::whereHas('employee', function($q) use ($customer) {
                $q->where('medical_institution_id', $customer->medical_institution_id);
                if ($customer->role === 'department_admin' && $customer->department_id) {
                    $q->inDepartment((int) $customer->department_id);
                }
            })
            ->where('status', 'in_use')
            ->sum('quantity');
            
            $loansThisMonth = EmployeeLoan::whereHas('employee', function($q) use ($customer) {
                $q->where('medical_institution_id', $customer->medical_institution_id);
                if ($customer->role === 'department_admin' && $customer->department_id) {
                    $q->inDepartment((int) $customer->department_id);
                }
            })
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity');
            
            $contractLimit = EmployeeContractPlan::query()
                ->whereIn('employee_id', (clone $employeeQuery)->where('status', 'employed')->pluck('id'))
                ->where(function ($q) {
                    $q->whereNull('assigned_until')->orWhere('assigned_until', '>=', now()->toDateString());
                })
                ->whereHas('customerContractPlan.contractPlan')
                ->with('customerContractPlan.contractPlan')
                ->get()
                ->sum(fn (EmployeeContractPlan $a) => (int) ($a->customerContractPlan?->contractPlan?->rentable_points ?? 0));
            
            $stats = [
                'total_employees' => $totalEmployees,
                'employed_employees' => $employedEmployees,
                'new_employees_this_month' => $newEmployeesThisMonth,
                'active_loans' => $activeLoans,
                'loans_this_month' => $loansThisMonth,
                'contract_limit' => $contractLimit,
                'pending_approvals' => (clone $baseQuery)->whereIn('status', ['pending', 'waiting_special_approval'])->count(),
                'special_approval_pending' => (clone $baseQuery)->where('status', 'waiting_special_approval')->count(),
                'applications_this_month' => (clone $baseQuery)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'applications_last_month' => (clone $baseQuery)
                    ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
                    ->count(),
            ];
            
            // Application trend data for customer (last 6 months) - Real data from database
            $trendData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();
                
                $monthQuery = (clone $baseQuery)->whereBetween('created_at', [$monthStart, $monthEnd]);
                
                $trendData[] = [
                    'month' => $month->format(app()->getLocale() === 'ja' ? 'n月' : 'M'),
                    'month_full' => $month->format(app()->getLocale() === 'ja' ? 'Y年n月' : 'M Y'),
                    'new_loan' => (clone $monthQuery)->where('type', 'new_loan')->count(),
                    'return' => (clone $monthQuery)->where('type', 'return')->count(),
                    'resize' => (clone $monthQuery)->where('type', 'resize')->count(),
                    'exchange' => (clone $monthQuery)->where('type', 'exchange')->count(),
                ];
            }
            
            // Recent applications
            $recentApplications = $baseQuery->with(['employee', 'department', 'items.uniformProduct'])
                ->orderByDesc('created_at')
                ->limit(4)
                ->get();
            
            // Department loan stats
            $departmentStats = Department::where('medical_institution_id', $customer->medical_institution_id)
                ->when($customer->role === 'department_admin' && $customer->department_id, function($q) use ($customer) {
                    $q->where('id', $customer->department_id);
                })
                ->withCount(['employees' => function($q) {
                    $q->where('status', 'employed');
                }])
                ->get()
                ->map(function($dept) {
                    $deptLoans = EmployeeLoan::whereHas('employee', function($q) use ($dept) {
                        $q->inDepartment((int) $dept->id)
                          ->where('status', 'employed');
                    })
                    ->where('status', 'in_use')
                    ->sum('quantity');

                    $deptEmployeeIds = Employee::query()
                        ->inDepartment((int) $dept->id)
                        ->where('status', 'employed')
                        ->pluck('id');
                    $deptLimit = EmployeeContractPlan::query()
                        ->whereIn('employee_id', $deptEmployeeIds)
                        ->where(function ($q) {
                            $q->whereNull('assigned_until')->orWhere('assigned_until', '>=', now()->toDateString());
                        })
                        ->with('customerContractPlan.contractPlan')
                        ->get()
                        ->sum(fn (EmployeeContractPlan $a) => (int) ($a->customerContractPlan?->contractPlan?->rentable_points ?? 0));
                    $utilization = $deptLimit > 0 ? round(($deptLoans / $deptLimit) * 100) : 0;
                    
                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'employees_count' => Employee::query()->inDepartment((int) $dept->id)->where('status', 'employed')->count(),
                        'loans' => $deptLoans,
                        'limit' => $deptLimit,
                        'utilization' => $utilization,
                    ];
                });
        }

        return view('dashboard', [
            'user' => $user,
            'customer' => $customer,
            'admin' => $admin,
            'stats' => $stats,
            'recentApplications' => $recentApplications,
            'queueApplications' => $queueApplications ?? collect(),
            'queueCounts' => $queueCounts ?? ['pending' => 0, 'special' => 0, 'cancel' => 0, 'total' => 0],
            'dashTab' => $admin
                ? ($request->string('tab')->toString()
                    ?: (in_array($admin->role, ['system_admin', 'operation'], true) ? 'queue' : 'overview'))
                : 'overview',
            'departmentStats' => $departmentStats,
            'medicalInstitution' => $medicalInstitution,
            'closingDay' => $closingDay,
            'trendData' => $trendData ?? [],
        ]);
    }
}


