<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Application;
use App\Models\MedicalInstitution;
use App\Models\EmployeeLoan;
use App\Models\UniformProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Support\Pagination;

class ApplicationApprovalController extends Controller
{
    private function ensureApprover(Request $request): AdminUser
    {
        $admin = $request->user()->adminUser;

        if (!$admin || !in_array($admin->role, ['system_admin', 'operation'], true)) {
            abort(403, __('pages.flash_0a61eb6b8f'));
        }

        return $admin;
    }

    public function index(Request $request): View
    {
        $query = Application::with(['employee', 'department', 'medicalInstitution']);

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($institutionId = $request->integer('institution_id')) {
            $query->where('medical_institution_id', $institutionId);
        }

        $applications = $query->orderByDesc('created_at')->paginate(Pagination::perPage($request))->withQueryString();

        $stats = [
            'pending' => Application::where('status', Application::STATUS_PENDING)->count(),
            'special' => Application::where('status', Application::STATUS_WAITING_SPECIAL)->count(),
            'approved_this_month' => Application::whereIn('status', [Application::STATUS_IN_PROGRESS, Application::STATUS_COMPLETED])
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'cancellations' => Application::where('status', Application::STATUS_CANCEL_REQUEST)->count(),
        ];

        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.applications.index', [
            'applications' => $applications,
            'stats' => $stats,
            'institutions' => $institutions,
        ]);
    }

    /**
     * 受注一覧（対応中／完了の申請）— SPEC 4.5
     */
    public function orders(Request $request): View
    {
        $this->ensureApprover($request);

        $query = Application::with(['employee', 'department', 'medicalInstitution', 'documents'])
            ->whereIn('status', [Application::STATUS_IN_PROGRESS, Application::STATUS_COMPLETED]);

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($institutionId = $request->integer('institution_id')) {
            $query->where('medical_institution_id', $institutionId);
        }

        $applications = $query->orderByDesc('accepted_at')->orderByDesc('id')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.orders.index', [
            'applications' => $applications,
            'institutions' => $institutions,
        ]);
    }

    public function show(Application $application): View
    {
        $application->load([
            'employee',
            'items.uniformProduct',
            'department',
            'deliveryLocation',
            'medicalInstitution',
            'requestedBy',
            'documents' => fn ($q) => $q->orderByDesc('issued_at'),
        ]);

        $institutionId = (int) $application->medical_institution_id;
        $pendingStatuses = [
            Application::STATUS_PENDING,
            Application::STATUS_WAITING_SPECIAL,
            Application::STATUS_CANCEL_REQUEST,
        ];

        $hospitalPendingQuery = Application::query()
            ->where('medical_institution_id', $institutionId)
            ->whereIn('status', $pendingStatuses);

        $hospitalPendingApps = (clone $hospitalPendingQuery)->count();
        $hospitalPendingOtherApps = (clone $hospitalPendingQuery)
            ->where('id', '!=', $application->id)
            ->count();

        $hospitalPendingItemQty = (int) DB::table('application_items')
            ->join('applications', 'applications.id', '=', 'application_items.application_id')
            ->where('applications.medical_institution_id', $institutionId)
            ->whereIn('applications.status', $pendingStatuses)
            ->whereIn('application_items.action', ['loan', 'exchange_in', 'transfer_change'])
            ->sum('application_items.quantity');

        $thisAppOutboundQty = (int) $application->items
            ->filter(fn ($i) => in_array($i->action ?: 'loan', ['loan', 'exchange_in', 'transfer_change'], true))
            ->sum('quantity');
        $thisAppReturnQty = (int) $application->items
            ->filter(fn ($i) => in_array($i->action, ['return', 'exchange_out'], true))
            ->sum('quantity');

        $otherPendingApps = Application::query()
            ->with(['employee:id,name,staff_no', 'items'])
            ->where('medical_institution_id', $institutionId)
            ->whereIn('status', $pendingStatuses)
            ->where('id', '!=', $application->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.applications.show', [
            'application' => $application,
            'latestDocuments' => $application->documents->where('is_latest', true)->keyBy('form_type'),
            'documentHistory' => $application->documents,
            'hospitalPendingApps' => $hospitalPendingApps,
            'hospitalPendingOtherApps' => $hospitalPendingOtherApps,
            'hospitalPendingItemQty' => $hospitalPendingItemQty,
            'thisAppOutboundQty' => $thisAppOutboundQty,
            'thisAppReturnQty' => $thisAppReturnQty,
            'otherPendingApps' => $otherPendingApps,
        ]);
    }

    public function updateMemos(Request $request, Application $application): RedirectResponse
    {
        $this->ensureApprover($request);

        $data = $request->validate([
            'memo1' => ['nullable', 'string', 'max:500'],
            'memo2' => ['nullable', 'string', 'max:500'],
        ]);

        $application->update([
            'memo1' => $data['memo1'] ?? null,
            'memo2' => $data['memo2'] ?? null,
        ]);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', __('pages.flash_7313a390f0'));
    }

    public function approve(Request $request, Application $application): RedirectResponse
    {
        $this->ensureApprover($request);

        if (!in_array($application->status, ['pending', 'waiting_special_approval'], true)) {
            return back()->with('error', __('pages.flash_b2b180ac53'));
        }

        $userId = $request->user()->id;
        $applicationId = $application->id;

        try {
            DB::transaction(function () use ($applicationId, $userId) {
                $application = Application::lockForUpdate()
                    ->with('items.uniformProduct')
                    ->findOrFail($applicationId);

                if (!in_array($application->status, ['pending', 'waiting_special_approval'], true)) {
                    throw new \Exception('この申請は承認できません。');
                }

                $hasSpecialApproval = $application->status === Application::STATUS_WAITING_SPECIAL;

                // Re-check rentable / category caps at accept (concurrent pending apps, plan changes)
                if (in_array($application->type, ['new_loan', 'exchange', 'resize', 'transfer'], true)
                    && $application->employee_id) {
                    $employee = \App\Models\Employee::find($application->employee_id);
                    if ($employee) {
                        $outbound = $application->items
                            ->filter(fn ($i) => in_array($i->action ?: 'loan', ['loan', 'exchange_in', 'transfer_change'], true))
                            ->map(fn ($i) => [
                                'uniform_product_id' => (int) $i->uniform_product_id,
                                'quantity' => (int) $i->quantity,
                            ])
                            ->values()
                            ->all();
                        $inbound = $application->items
                            ->filter(fn ($i) => in_array($i->action, ['return', 'exchange_out'], true))
                            ->map(fn ($i) => [
                                'uniform_product_id' => (int) $i->uniform_product_id,
                                'quantity' => (int) $i->quantity,
                            ])
                            ->values()
                            ->all();

                        $violation = app(\App\Services\EmployeePlanEligibilityService::class)
                            ->loanLimitViolation(
                                $employee,
                                $outbound,
                                $inbound,
                                null,
                                true,
                                (int) $application->id
                            );
                        if ($violation) {
                            $messageKey = ($violation['mode'] ?? '') === 'category'
                                ? 'pages.rentable_category_limit_hard_block'
                                : 'pages.rentable_limit_hard_block';
                            throw new \Exception(__($messageKey, [
                                'category' => isset($violation['category'])
                                    ? \App\Models\UniformCategory::labelFor($violation['category'])
                                    : '',
                                'limit' => $violation['limit'],
                                'projected' => $violation['projected'],
                            ]));
                        }
                    }
                }

                match ($application->type) {
                    'return' => $this->processReturnApproval($application),
                    'exchange', 'resize', 'transfer' => $this->processExchangeOrResizeApproval($application, $hasSpecialApproval),
                    default => $this->processNewLoanApproval($application, $hasSpecialApproval),
                };

                $application->status = Application::STATUS_IN_PROGRESS;
                $application->approved_at = now();
                $application->accepted_at = now();
                $application->approved_by_admin_user_id = $userId;

                if ($application->type === 'return') {
                    $application->return_date = $application->return_date ?? ($application->end_date ?? now()->toDateString());
                }

                $application->save();
            });

            // 通知はコミット後に実行（失敗しても受付は維持）
            // SPEC: 請求は締め日基準の PlanBillingService スナップショットのみ。
            try {
                $approved = Application::with(['medicalInstitution', 'department', 'items'])->findOrFail($applicationId);
                \App\Services\NotificationService::notifyApplicationApproved($approved);
            } catch (\Exception $e) {
                Log::error('Post-approval notification failed', [
                    'application_id' => $applicationId,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $approved = Application::findOrFail($applicationId);
                app(\App\Services\ApplicationPdfService::class)->issueAll($approved, $request->user());
            } catch (\Exception $e) {
                Log::error('PDF issue after accept failed', [
                    'application_id' => $applicationId,
                    'error' => $e->getMessage(),
                ]);
                return back()->with('status', __('pages.flash_aaa5e96fa9'));
            }
        } catch (\Exception $e) {
            Log::error('Application approval failed', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('pages.flash_54ae2dae16'));
    }

    /**
     * 出荷登録 → 完了
     */
    public function complete(Request $request, Application $application): RedirectResponse
    {
        $this->ensureApprover($request);

        if (! $application->isCompletable()) {
            return back()->with('error', __('pages.flash_9bf4ce5709'));
        }

        $application->status = Application::STATUS_COMPLETED;
        $application->shipped_at = now();
        $application->completed_at = now();
        $application->save();

        return back()->with('status', __('pages.flash_19eb46a946'));
    }

    /**
     * 新規貸与: 在庫減算 + 貸与加算
     */
    private function processNewLoanApproval(Application $application, bool $hasSpecialApproval): void
    {
        $this->assertStockForOutboundItems($application, $hasSpecialApproval);

        foreach ($application->items as $item) {
            $action = $item->action ?: 'loan';
            if (!in_array($action, ['loan', 'exchange_in', 'transfer_change'], true)) {
                continue;
            }
            $this->applyLoanOutbound($application, $item, $hasSpecialApproval);
        }
    }

    /**
     * 返却: 貸与減算 + 在庫戻し
     */
    private function processReturnApproval(Application $application): void
    {
        foreach ($application->items as $item) {
            $action = $item->action ?: 'return';
            if (! in_array($action, ['return', 'exchange_out'], true)) {
                continue;
            }
            $this->applyLoanReturn($application, $item);
        }
    }

    /**
     * 交換／サイズ変更／異動（部分変更）: exchange_out/return は返却、loan/exchange_in は新規貸与
     */
    private function processExchangeOrResizeApproval(Application $application, bool $hasSpecialApproval): void
    {
        $hasOutbound = $application->items->contains(fn ($i) => in_array($i->action, ['loan', 'exchange_in'], true) || empty($i->action));

        if ($hasOutbound) {
            $this->assertStockForOutboundItems($application, $hasSpecialApproval);
        }

        foreach ($application->items as $item) {
            $action = $item->action ?: 'loan';

            if (in_array($action, ['return', 'exchange_out'], true)) {
                $this->applyLoanReturn($application, $item);
                continue;
            }

            if (in_array($action, ['loan', 'exchange_in'], true)) {
                $this->applyLoanOutbound($application, $item, $hasSpecialApproval);
            }
        }
    }

    private function assertStockForOutboundItems(Application $application, bool $hasSpecialApproval): void
    {
        if ($hasSpecialApproval) {
            Log::info('Application approved with special approval; stock may go negative', [
                'application_id' => $application->id,
                'special_approval_reason' => $application->special_approval_reason,
            ]);
            return;
        }

        foreach ($application->items as $item) {
            $action = $item->action ?: 'loan';
            if (!in_array($action, ['loan', 'exchange_in', 'transfer_change'], true)) {
                continue;
            }

            $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
            if ($product && $product->stock_quantity < $item->quantity) {
                throw new \Exception("在庫不足: {$product->name} (在庫: {$product->stock_quantity}, 必要: {$item->quantity})");
            }
        }
    }

    private function applyLoanOutbound(Application $application, $item, bool $hasSpecialApproval): void
    {
        $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
        if ($product) {
            $next = (int) $product->stock_quantity - (int) $item->quantity;
            if (! $hasSpecialApproval && $next < 0) {
                throw new \Exception("在庫不足: {$product->name}");
            }
            // unsignedInteger cannot store negatives — clamp at 0 for special-approval backorder
            $product->stock_quantity = max(0, $next);
            $product->save();
        }

        $loan = EmployeeLoan::lockForUpdate()->firstOrNew([
            'employee_id' => $application->employee_id,
            'uniform_product_id' => $item->uniform_product_id,
        ]);

        $loan->quantity = ($loan->quantity ?? 0) + $item->quantity;
        $loan->status = 'in_use';
        $loan->application_id = $application->id;
        $loan->loan_start_date = $application->start_date ?? now();
        $loan->loan_end_date = null;
        $loan->save();
    }

    private function applyLoanReturn(Application $application, $item): void
    {
        $requested = (int) $item->quantity;

        $loan = EmployeeLoan::lockForUpdate()
            ->where('employee_id', $application->employee_id)
            ->where('uniform_product_id', $item->uniform_product_id)
            ->where('status', 'in_use')
            ->first();

        $held = $loan ? (int) ($loan->quantity ?? 0) : 0;
        if (! $loan || $held < $requested) {
            $productName = optional($item->uniformProduct)->name ?? ('#'.$item->uniform_product_id);
            throw new \Exception(__('pages.return_approve_exceeds_held', [
                'product' => $productName,
                'held' => $held,
                'requested' => $requested,
            ]));
        }

        $loan->quantity = $held - $requested;
        if ($loan->quantity <= 0) {
            $loan->quantity = 0;
            $loan->status = 'returned';
            $loan->loan_end_date = $application->end_date ?? $application->return_date ?? now();
        }
        $loan->application_id = $application->id;
        $loan->save();

        $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
        if ($product) {
            $product->increment('stock_quantity', $requested);
        }
    }

    public function reject(Request $request, Application $application): RedirectResponse
    {
        $this->ensureApprover($request);

        if (!in_array($application->status, ['pending', 'waiting_special_approval'], true)) {
            return back()->with('error', __('pages.flash_667b6ceddf'));
        }

        $application->status = 'rejected';
        $application->approved_at = now();
        $application->approved_by_admin_user_id = $request->user()->id;
        $application->rejection_reason = $request->input('rejection_reason');
        $application->save();

        \App\Services\NotificationService::notifyApplicationRejected($application);

        return back()->with('status', __('pages.flash_3782da7077'));
    }

    public function approveCancel(Request $request, Application $application): RedirectResponse
    {
        $this->ensureApprover($request);

        $userId = $request->user()->id;
        $applicationId = $application->id;

        try {
            DB::transaction(function () use ($applicationId, $userId) {
                $application = Application::lockForUpdate()
                    ->with('items')
                    ->findOrFail($applicationId);

                if ($application->status !== 'cancel_request') {
                    throw new \Exception(__('pages.flash_07fcf2b9a8'));
                }

                // Inventory only moved on accept — reverse only when accept already ran
                $shouldReverseInventory = $application->accepted_at !== null;

                if ($shouldReverseInventory) {
                    foreach ($application->items as $item) {
                        $action = $item->action ?: 'loan';
                        $qty = (int) $item->quantity;

                        // Undo outbound loans (stock was decremented on approve)
                        if (in_array($action, ['loan', 'exchange_in', 'transfer_change'], true)) {
                            $loan = EmployeeLoan::lockForUpdate()
                                ->where('employee_id', $application->employee_id)
                                ->where('uniform_product_id', $item->uniform_product_id)
                                ->where('status', 'in_use')
                                ->first();

                            if ($loan) {
                                $held = (int) ($loan->quantity ?? 0);
                                $reversed = min($qty, max(0, $held));
                                $loan->quantity = $held - $reversed;
                                if ($loan->quantity <= 0) {
                                    $loan->quantity = 0;
                                    $loan->status = 'returned';
                                    $loan->loan_end_date = now();
                                }
                                $loan->save();

                                if ($reversed > 0) {
                                    $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
                                    if ($product) {
                                        $product->increment('stock_quantity', $reversed);
                                    }
                                }
                            }
                        }

                        // Undo returns (stock was restocked / loan reduced on approve)
                        if (in_array($action, ['return', 'exchange_out'], true)) {
                            $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
                            if ($product) {
                                if ((int) $product->stock_quantity < $qty) {
                                    throw new \Exception(__('pages.cancel_blocked_insufficient_stock_to_reverse'));
                                }
                                $product->stock_quantity = (int) $product->stock_quantity - $qty;
                                $product->save();
                            }

                            $loan = EmployeeLoan::lockForUpdate()->firstOrNew([
                                'employee_id' => $application->employee_id,
                                'uniform_product_id' => $item->uniform_product_id,
                            ]);
                            $loan->quantity = (int) ($loan->quantity ?? 0) + $qty;
                            $loan->status = 'in_use';
                            $loan->loan_end_date = null;
                            if (! $loan->loan_start_date) {
                                $loan->loan_start_date = now();
                            }
                            $loan->application_id = $application->id;
                            $loan->save();
                        }
                    }
                }

                $application->status = 'cancel_approved';
                $application->approved_at = now();
                $application->approved_by_admin_user_id = $userId;
                $application->save();
            });
        } catch (\Exception $e) {
            Log::error('Cancel approval failed', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        try {
            \App\Services\NotificationService::notifyCancelApproved(Application::findOrFail($applicationId));
        } catch (\Exception $e) {
            Log::error('Cancel approved notification failed', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('status', __('pages.flash_3bf28c3894'));
    }

    public function rejectCancel(Request $request, Application $application): RedirectResponse
    {
        $this->ensureApprover($request);

        if ($application->status !== 'cancel_request') {
            return back()->with('error', __('pages.flash_07fcf2b9a8'));
        }

        // Resume prior workflow so ops can continue shipping after rejecting cancel
        $restoreStatus = $application->statusAfterCancelReject();

        $application->status = $restoreStatus;
        $application->approved_by_admin_user_id = $request->user()->id;
        $application->rejection_reason = $request->input('rejection_reason');
        $application->save();

        try {
            \App\Services\NotificationService::notifyCancelRejected($application);
        } catch (\Exception $e) {
            Log::error('Cancel rejected notification failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }

        $label = $application->statusLabel();

        return back()->with('status', "キャンセルを却下しました。申請を「{$label}」に戻しました。");
    }

    public function exportCsv(Request $request)
    {
        $query = Application::with([
            'employee.positions.jobType',
            'department',
            'medicalInstitution',
            'items.uniformProduct',
        ]);

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($institutionId = $request->integer('institution_id')) {
            $query->where('medical_institution_id', $institutionId);
        }

        $applications = $query->orderByDesc('created_at')->get();

        $filename = 'applications_' . date('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($applications) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                '申請ID',
                '申請種別',
                '医療機関',
                '部署',
                '職員名',
                '職員番号',
                '職種',
                '開始日',
                '状態',
                '商品コード',
                '商品名',
                'サイズ',
                '数量',
                '申請日時',
                '承認日時',
            ]);

            foreach ($applications as $app) {
                if ($app->items->count() > 0) {
                    foreach ($app->items as $item) {
                        fputcsv($file, [
                            '#APP-' . str_pad($app->id, 4, '0', STR_PAD_LEFT),
                            $app->typeLabel(),
                            $app->medicalInstitution?->name ?? '',
                            $app->department?->name ?? '',
                            $app->employee?->name ?? '',
                            $app->employee?->staff_no ?? '',
                            $app->employee?->positions->first()?->jobType?->name ?? '',
                            $app->start_date?->format('Y-m-d') ?? '',
                            $app->statusLabel(),
                            $item->uniformProduct?->product_code ?? '',
                            $item->uniformProduct?->name ?? '',
                            $item->uniformProduct?->size ?? '',
                            $item->quantity,
                            $app->created_at?->format('Y-m-d H:i:s') ?? '',
                            $app->approved_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                } else {
                    fputcsv($file, [
                        '#APP-' . str_pad($app->id, 4, '0', STR_PAD_LEFT),
                        $app->typeLabel(),
                        $app->medicalInstitution?->name ?? '',
                        $app->department?->name ?? '',
                        $app->employee?->name ?? '',
                        $app->employee?->staff_no ?? '',
                        $app->employee?->positions->first()?->jobType?->name ?? '',
                        $app->start_date?->format('Y-m-d') ?? '',
                        $app->statusLabel(),
                        '',
                        '',
                        '',
                        '',
                        $app->created_at?->format('Y-m-d H:i:s') ?? '',
                        $app->approved_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
