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
        if (! $admin || ! in_array($admin->role, ['system_admin', 'accounting'], true)) {
            abort(403, __('pages.flash_63e10f27d6'));
        }
    }

    private function checkViewPermission(): void
    {
        $admin = request()->user()->adminUser ?? null;
        if (! $admin || ! in_array($admin->role, ['system_admin', 'operation', 'accounting', 'sales'], true)) {
            abort(403, __('pages.flash_aba9d2be71'));
        }
    }

    public function index(Request $request): View
    {
        $this->checkViewPermission();

        $yearMonth = $request->input('billing_month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', (string) $yearMonth)) {
            $yearMonth = now()->format('Y-m');
        }

        $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $institutions = MedicalInstitution::orderBy('name')->get();
        $planService = app(PlanBillingService::class);

        $monthSnapshots = BillingSnapshot::query()
            ->whereBetween('target_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('medical_institution_id');

        $confirmedPlanCounts = CustomerContractPlan::query()
            ->where('status', 'confirmed')
            ->selectRaw('medical_institution_id, COUNT(*) as cnt')
            ->groupBy('medical_institution_id')
            ->pluck('cnt', 'medical_institution_id');

        $fullBoard = $institutions->map(function (MedicalInstitution $institution) use (
            $planService,
            $yearMonth,
            $monthSnapshots,
            $confirmedPlanCounts
        ) {
            $closingDate = $planService->defaultAsOfDate($institution, $yearMonth);
            $closingDateStr = $closingDate->toDateString();
            $snapshots = $monthSnapshots->get($institution->id, collect());
            // Prefer snapshot taken on this hospital's 締め日 for the month
            $latest = $snapshots->first(function ($s) use ($closingDateStr) {
                return optional($s->target_date)->toDateString() === $closingDateStr;
            }) ?? $snapshots->first();
            $hasConfirmed = (int) ($confirmedPlanCounts[$institution->id] ?? 0) > 0;
            $status = ! $hasConfirmed ? 'no_contract' : ($latest ? 'generated' : 'pending');

            $summary = $latest?->data['summary'] ?? [];

            return [
                'institution' => $institution,
                'closing_day' => (int) ($institution->billing_closing_day ?: 1),
                'closing_date' => $closingDateStr,
                'status' => $status,
                'snapshot' => $latest,
                'snapshot_count' => $snapshots->count(),
                'amount' => $summary['billing_amount']
                    ?? $summary['total_amount_ex_tax']
                    ?? null,
                'assigned_count' => $summary['total_assigned']
                    ?? $summary['total_targets']
                    ?? null,
            ];
        });

        $boardStats = [
            'pending' => $fullBoard->where('status', 'pending')->count(),
            'generated' => $fullBoard->where('status', 'generated')->count(),
            'no_contract' => $fullBoard->where('status', 'no_contract')->count(),
            'total' => $fullBoard->count(),
        ];

        $statusFilter = $request->input('status_filter'); // pending|generated|no_contract|all
        if (in_array($statusFilter, ['pending', 'generated', 'no_contract'], true)) {
            $institutionMonthStatus = $fullBoard->where('status', $statusFilter)->values();
        } else {
            $institutionMonthStatus = $fullBoard->values();
            $statusFilter = 'all';
        }

        $totalBillingSnapshots = BillingSnapshot::query()
            ->whereIn('id', BillingSnapshot::latestIds())
            ->count();
        $thisMonthBillingSnapshots = BillingSnapshot::query()
            ->whereIn('id', BillingSnapshot::latestIds($monthStart->toDateString(), $monthEnd->toDateString()))
            ->count();

        $totalBillingAmount = BillingSnapshot::sumBillingAmount();
        $thisMonthBillingAmount = BillingSnapshot::sumBillingAmount(
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );

        return view('admin.billing.index', [
            'institutions' => $institutions,
            'billingMonth' => $yearMonth,
            'institutionMonthStatus' => $institutionMonthStatus,
            'boardStats' => $boardStats,
            'statusFilter' => $statusFilter ?: 'all',
            'stats' => [
                'total_billing_snapshots' => $totalBillingSnapshots,
                'this_month_billing_snapshots' => $thisMonthBillingSnapshots,
                'total_billing_amount' => $totalBillingAmount,
                'this_month_billing_amount' => $thisMonthBillingAmount,
            ],
        ]);
    }

    public function history(Request $request): View
    {
        $this->checkViewPermission();

        $yearMonth = $request->input('billing_month');
        if ($yearMonth && ! preg_match('/^\d{4}-\d{2}$/', (string) $yearMonth)) {
            $yearMonth = null;
        }

        $query = BillingSnapshot::with(['medicalInstitution', 'generatedBy'])
            ->orderByDesc('target_date')
            ->orderByDesc('created_at');

        $institutionId = $request->integer('medical_institution_id');
        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        if ($yearMonth) {
            $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $query->whereBetween('target_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ]);
        }

        $billingSnapshots = $query->paginate(Pagination::perPage($request))->withQueryString();
        $institutions = MedicalInstitution::orderBy('name')->get();

        $billingCounts = ['institution' => []];
        foreach ($institutions as $institution) {
            $snapshotCount = BillingSnapshot::where('medical_institution_id', $institution->id)->count();
            if ($snapshotCount > 0) {
                $billingCounts['institution'][$institution->id] = $snapshotCount;
            }
        }

        return view('admin.billing.history', [
            'billingSnapshots' => $billingSnapshots,
            'institutions' => $institutions,
            'billingCounts' => $billingCounts,
            'billingMonth' => $yearMonth,
            'selectedInstitutionId' => $institutionId ?: null,
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

        $existing = BillingSnapshot::query()
            ->where('medical_institution_id', $institution->id)
            ->whereDate('target_date', $data['target_date'])
            ->whereNull('department_id')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->update([
                'data' => $billingData,
                'generated_by_admin_user_id' => $request->user()->id,
            ]);
            $snapshot = $existing->fresh();

            // Drop older duplicates for the same institution + closing date
            BillingSnapshot::query()
                ->where('medical_institution_id', $institution->id)
                ->whereDate('target_date', $data['target_date'])
                ->whereNull('department_id')
                ->where('id', '!=', $snapshot->id)
                ->delete();

            $status = '請求データを更新しました。';
        } else {
            $snapshot = BillingSnapshot::create([
                'medical_institution_id' => $institution->id,
                'department_id' => null,
                'target_date' => $data['target_date'],
                'data' => $billingData,
                'generated_by_admin_user_id' => $request->user()->id,
            ]);
            $status = '請求データを生成しました。';
        }

        return redirect()
            ->route('admin.billing.show', $snapshot)
            ->with('status', $status);
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
