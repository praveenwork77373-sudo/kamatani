<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Application;
use App\Models\ApplicationItem;
use App\Models\MedicalInstitution;
use App\Models\EmployeeLoan;
use App\Models\UniformProduct;
use App\Models\BillingSnapshot;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Carbon\Carbon;

class ApplicationApprovalController extends Controller
{
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

        $applications = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $stats = [
            'pending' => Application::where('status', 'pending')->count(),
            'special' => Application::where('status', 'waiting_special_approval')->count(),
            'approved_this_month' => Application::where('status', 'approved')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'cancellations' => Application::where('status', 'cancel_request')->count(),
        ];

        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.applications.index', [
            'applications' => $applications,
            'stats' => $stats,
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
            'requestedBy'
        ]);

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function approve(Request $request, Application $application): RedirectResponse
    {
        /** @var AdminUser|null $admin */
        $admin = $request->user()->adminUser;

        if (!$admin || !in_array($admin->role, ['system_admin', 'operation'])) {
            abort(403, '承認権限がありません。');
        }

        if (!in_array($application->status, ['pending', 'waiting_special_approval'])) {
            return back()->with('error', 'この申請は承認できません。');
        }

        $userId = $request->user()->id;
        $applicationId = $application->id;

        try {
            DB::transaction(function () use ($applicationId, $admin, $userId) {
            // Reload application with lock to prevent race conditions
            $application = Application::lockForUpdate()->with('items.uniformProduct')->findOrFail($applicationId);
            
            // Check if application has special approval - if so, allow approval even with insufficient stock
            $hasSpecialApproval = !empty($application->special_approval_reason);
            
            // 在庫チェック (only if no special approval)
            if (!$hasSpecialApproval) {
                foreach ($application->items as $item) {
                    // Lock the product row to prevent concurrent modifications
                    $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
                    if ($product && $product->stock_quantity < $item->quantity) {
                        throw new \Exception("在庫不足: {$product->name} (在庫: {$product->stock_quantity}, 必要: {$item->quantity})");
                    }
                }
            } else {
                // Log that special approval is being used for insufficient stock
                Log::info('Application approved with special approval despite insufficient stock', [
                    'application_id' => $application->id,
                    'special_approval_reason' => $application->special_approval_reason,
                ]);
            }

            // 在庫を減らす (only if product exists and has stock, or if special approval)
            foreach ($application->items as $item) {
                $product = UniformProduct::lockForUpdate()->find($item->uniform_product_id);
                if ($product) {
                    // If special approval, allow negative stock (backorder scenario)
                    if ($hasSpecialApproval) {
                        // Allow stock to go negative for special approval cases
                        $product->stock_quantity = max(0, $product->stock_quantity - $item->quantity);
                        $product->save();
                    } else {
                        // Normal case: decrement stock (already validated above)
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                }

                // 貸与記録を作成/更新
                $loan = EmployeeLoan::firstOrNew([
                    'employee_id' => $application->employee_id,
                    'uniform_product_id' => $item->uniform_product_id,
                ]);

                $loan->quantity = ($loan->quantity ?? 0) + $item->quantity;
                $loan->status = 'in_use';
                $loan->application_id = $application->id;
                $loan->loan_start_date = $application->start_date ?? now();
                // loan_end_date is left null (ongoing loan)
                $loan->save();
            }

            $application->status = 'approved';
            $application->approved_at = now();
            $application->approved_by_admin_user_id = $userId;
            
            // Set billing_status if column exists
            if (Schema::hasColumn('applications', 'billing_status')) {
                $application->billing_status = 'waiting';
            }
            
            $application->save();
            
            // Reload application to ensure we have latest data
            $application->refresh();
            $application->load(['medicalInstitution', 'items']);

            // 通知を送信
            \App\Services\NotificationService::notifyApplicationApproved($application);
            
                // 自動的に請求データを生成（月次で、まだ生成されていない場合）
                // Note: This is called inside transaction, but errors are caught and logged
                \App\Services\AutoBillingService::generateBillingForApprovedApplication($application, $userId);
            });
        } catch (\Exception $e) {
            Log::error('Application approval failed', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', '申請を承認しました。');
    }

    public function reject(Request $request, Application $application): RedirectResponse
    {
        /** @var AdminUser|null $admin */
        $admin = $request->user()->adminUser;

        if (!$admin || !in_array($admin->role, ['system_admin', 'operation'])) {
            abort(403);
        }

        if (!in_array($application->status, ['pending', 'waiting_special_approval'])) {
            return back()->with('error', 'この申請は却下できません。');
        }

        $application->status = 'rejected';
        $application->approved_at = now();
        $application->approved_by_admin_user_id = $request->user()->id;
        $application->rejection_reason = $request->input('rejection_reason');
        $application->save();

        // 通知を送信
        \App\Services\NotificationService::notifyApplicationRejected($application);

        return back()->with('status', '申請を却下しました。');
    }

    public function approveCancel(Request $request, Application $application): RedirectResponse
    {
        /** @var AdminUser|null $admin */
        $admin = $request->user()->adminUser;

        if (!$admin || !in_array($admin->role, ['system_admin', 'operation'])) {
            abort(403);
        }

        if ($application->status !== 'cancel_request') {
            return back()->with('error', 'キャンセル申請がありません。');
        }

        DB::transaction(function () use ($application, $admin) {
            // 貸与記録を返却状態に
            foreach ($application->items as $item) {
                $loan = EmployeeLoan::where('employee_id', $application->employee_id)
                    ->where('uniform_product_id', $item->uniform_product_id)
                    ->first();

                if ($loan) {
                    $loan->quantity = max(0, ($loan->quantity ?? 0) - $item->quantity);
                    if ($loan->quantity <= 0) {
                        $loan->status = 'returned';
                    }
                    $loan->save();
                }

                // 在庫を戻す
                $product = UniformProduct::find($item->uniform_product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            $application->status = 'cancel_approved';
            $application->approved_at = now();
            $application->approved_by_admin_user_id = $admin->id;
            $application->save();
        });

        return back()->with('status', 'キャンセルを承認しました。');
    }

    public function rejectCancel(Request $request, Application $application): RedirectResponse
    {
        /** @var AdminUser|null $admin */
        $admin = $request->user()->adminUser;

        if (!$admin || !in_array($admin->role, ['system_admin', 'operation'])) {
            abort(403);
        }

        if ($application->status !== 'cancel_request') {
            return back()->with('error', 'キャンセル申請がありません。');
        }

        $application->status = 'cancel_rejected';
        $application->approved_at = now();
        $application->approved_by_admin_user_id = $admin->id;
        $application->rejection_reason = $request->input('rejection_reason');
        $application->save();

        return back()->with('status', 'キャンセルを却下しました。');
    }

    public function exportCsv(Request $request)
    {
        $query = Application::with([
            'employee.positions.jobType',
            'department',
            'medicalInstitution',
            'items.uniformProduct'
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
            
            // BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
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
            
            // Data
            foreach ($applications as $app) {
                $typeLabels = [
                    'new_loan' => '新規貸与',
                    'resize' => 'サイズ交換',
                    'return' => '返却',
                    'exchange' => '交換',
                    'transfer' => '譲渡',
                ];
                
                $statusLabels = [
                    'pending' => '未処理',
                    'waiting_special_approval' => '特別承認待ち',
                    'approved' => '承認済み',
                    'rejected' => '却下',
                    'cancel_request' => 'キャンセル依頼中',
                    'cancel_approved' => 'キャンセル承認',
                    'cancel_rejected' => 'キャンセル却下',
                ];
                
                if ($app->items->count() > 0) {
                    foreach ($app->items as $item) {
                        fputcsv($file, [
                            '#APP-' . str_pad($app->id, 4, '0', STR_PAD_LEFT),
                            $typeLabels[$app->type] ?? $app->type,
                            $app->medicalInstitution?->name ?? '',
                            $app->department?->name ?? '',
                            $app->employee?->name ?? '',
                            $app->employee?->staff_no ?? '',
                            $app->employee?->positions->first()?->jobType?->name ?? '',
                            $app->start_date?->format('Y-m-d') ?? '',
                            $statusLabels[$app->status] ?? $app->status,
                            $item->uniformProduct?->product_code ?? '',
                            $item->uniformProduct?->name ?? '',
                            $item->uniformProduct?->size ?? '',
                            $item->quantity,
                            $app->created_at?->format('Y-m-d H:i:s') ?? '',
                            $app->approved_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                } else {
                    // If no items, still export the application
                    fputcsv($file, [
                        '#APP-' . str_pad($app->id, 4, '0', STR_PAD_LEFT),
                        $typeLabels[$app->type] ?? $app->type,
                        $app->medicalInstitution?->name ?? '',
                        $app->department?->name ?? '',
                        $app->employee?->name ?? '',
                        $app->employee?->staff_no ?? '',
                        $app->employee?->positions->first()?->jobType?->name ?? '',
                        $app->start_date?->format('Y-m-d') ?? '',
                        $statusLabels[$app->status] ?? $app->status,
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
