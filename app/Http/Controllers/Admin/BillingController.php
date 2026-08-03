<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingSnapshot;
use App\Models\CustomerContractPlan;
use App\Models\MedicalInstitution;
use App\Services\PlanBillingExportService;
use App\Services\PlanBillingService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    private function checkManagePermission(): void
    {
        $admin = request()->user()->adminUser ?? null;
        if (! $admin || ! in_array($admin->role, ['system_admin', 'operation', 'accounting'], true)) {
            abort(403, '請求データの管理権限がありません。');
        }
    }

    private function checkViewPermission(): void
    {
        $admin = request()->user()->adminUser ?? null;
        if (! $admin || ! in_array($admin->role, ['system_admin', 'operation', 'accounting', 'sales'], true)) {
            abort(403, '請求データの閲覧権限がありません。');
        }
    }

    public function index(Request $request): View
    {
        $this->checkViewPermission();

        $query = BillingSnapshot::with(['medicalInstitution', 'department', 'generatedBy'])
            ->orderByDesc('target_date')
            ->orderByDesc('created_at');

        $institutionId = $request->integer('medical_institution_id');
        $departmentId = $request->integer('department_id');

        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $billingSnapshots = $query->paginate(Pagination::perPage($request))->withQueryString();

        $institutionsWithDepartments = MedicalInstitution::with('departments')
            ->orderBy('name')
            ->get();

        $billingCounts = [
            'institution' => [],
            'department' => [],
        ];

        foreach ($institutionsWithDepartments as $institution) {
            $snapshotCount = BillingSnapshot::where('medical_institution_id', $institution->id)->count();
            if ($snapshotCount > 0) {
                $billingCounts['institution'][$institution->id] = $snapshotCount;
            }

            foreach ($institution->departments as $department) {
                $deptCount = BillingSnapshot::where('medical_institution_id', $institution->id)
                    ->where('department_id', $department->id)
                    ->count();

                $deptBillingAmount = 0;
                $deptEmployeeCount = 0;
                $deptReturnPoints = 0;

                if ($deptCount > 0) {
                    $latestSnapshot = BillingSnapshot::where('medical_institution_id', $institution->id)
                        ->where('department_id', $department->id)
                        ->orderByDesc('target_date')
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestSnapshot) {
                        $summary = ($latestSnapshot->data ?? [])['summary'] ?? [];
                        $deptBillingAmount = $summary['billing_amount'] ?? 0;
                        $deptEmployeeCount = $summary['total_targets'] ?? 0;
                        $deptReturnPoints = $summary['return_points'] ?? 0;
                    }
                }

                $billingCounts['department'][$department->id] = [
                    'count' => $deptCount,
                    'billing_amount' => $deptBillingAmount,
                    'employee_count' => $deptEmployeeCount,
                    'return_points' => $deptReturnPoints,
                ];
            }
        }

        $totalBillingSnapshots = BillingSnapshot::count();
        $thisMonthBillingSnapshots = BillingSnapshot::whereBetween('target_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
        ])->count();

        $totalBillingAmount = BillingSnapshot::get()->sum(function ($snapshot) {
            return ($snapshot->data['summary']['billing_amount'] ?? 0);
        });

        $thisMonthBillingAmount = BillingSnapshot::whereBetween('target_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
        ])->get()->sum(function ($snapshot) {
            return ($snapshot->data['summary']['billing_amount'] ?? 0);
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
        $institutionId = $request->input('medical_institution_id');
        $planService = app(PlanBillingService::class);

        $targetDate = $request->input('target_date');
        $previewData = null;
        $selectedInstitution = null;

        if ($institutionId) {
            $selectedInstitution = MedicalInstitution::find($institutionId);
            if ($selectedInstitution && ! $targetDate) {
                $targetDate = $planService->defaultAsOfDate(
                    $selectedInstitution,
                    $request->input('target_month')
                )->toDateString();
            }
            if ($selectedInstitution && $targetDate) {
                $previewData = $planService->build($selectedInstitution, $targetDate);
            }
        }

        if (! $targetDate) {
            $targetDate = now()->startOfMonth()->toDateString();
        }

        return view('admin.billing.create', [
            'institutions' => $institutions,
            'targetDate' => $targetDate,
            'selectedInstitutionId' => $institutionId,
            'selectedInstitution' => $selectedInstitution,
            'contract' => null,
            'previewData' => $previewData,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->checkManagePermission();

        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'target_date' => ['required', 'date'],
        ]);

        $institution = MedicalInstitution::findOrFail($data['medical_institution_id']);

        $hasConfirmedPlans = CustomerContractPlan::query()
            ->where('medical_institution_id', $institution->id)
            ->where('status', 'confirmed')
            ->exists();

        if (! $hasConfirmedPlans) {
            return back()->withErrors([
                'medical_institution_id' => '本登録済みの顧客契約プランがありません。先に契約プランを本登録してください。',
            ])->withInput();
        }

        $billingData = app(PlanBillingService::class)->build($institution, $data['target_date']);

        $snapshot = BillingSnapshot::create([
            'medical_institution_id' => $institution->id,
            'department_id' => null,
            'target_date' => $data['target_date'],
            'data' => $billingData,
            'generated_by_admin_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.billing.show', $snapshot)
            ->with('status', '契約プラン×割当人数の請求データを生成しました。');
    }

    public function export(BillingSnapshot $snapshot)
    {
        $this->checkViewPermission();

        $data = $snapshot->data ?? [];
        if (($data['model'] ?? null) !== 'plan_based') {
            $institution = $snapshot->medicalInstitution;
            if (! $institution) {
                abort(404);
            }
            $data = app(PlanBillingService::class)->build(
                $institution,
                $snapshot->target_date ?? now()
            );
            $snapshot->setAttribute('data', $data);
        }

        return app(PlanBillingExportService::class)->download($snapshot);
    }

    public function show(BillingSnapshot $snapshot): View
    {
        $this->checkViewPermission();
        $snapshot->load(['medicalInstitution', 'generatedBy']);

        return view('admin.billing.show', ['snapshot' => $snapshot]);
    }
}
