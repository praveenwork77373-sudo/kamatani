<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use App\Support\Pagination;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backupService
    ) {
    }

    public function index(Request $request): View
    {
        $settings = $this->backupService->getSettings();
        $backups = $this->backupService->listBackups(Pagination::perPage($request));

        return view('admin.database-backups.index', [
            'settings' => $settings,
            'backups' => $backups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $backup = $this->backupService->createBackup($request->user()->id, 'manual');
            $this->backupService->pruneOldBackups();

            return redirect()
                ->route('admin.database-backups.index')
                ->with('status', __('pages.backup_created', ['filename' => $backup->filename]));
        } catch (Throwable $e) {
            Log::error('Manual database backup failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.database-backups.index')
                ->with('error', __('pages.backup_failed', ['message' => $e->getMessage()]));
        }
    }

    public function download(DatabaseBackup $databaseBackup): BinaryFileResponse|RedirectResponse
    {
        if ($databaseBackup->status !== 'success' || ! $databaseBackup->fileExists()) {
            return redirect()
                ->route('admin.database-backups.index')
                ->with('error', __('pages.backup_file_missing'));
        }

        return response()->download(
            $databaseBackup->absolutePath(),
            $databaseBackup->filename
        );
    }

    public function restore(Request $request, DatabaseBackup $databaseBackup): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:RESTORE'],
        ], [
            'confirm.in' => __('pages.backup_restore_confirm_invalid'),
        ]);

        try {
            $this->backupService->restore($databaseBackup);

            return redirect()
                ->route('admin.database-backups.index')
                ->with('status', __('pages.backup_restored', ['filename' => $databaseBackup->filename]));
        } catch (Throwable $e) {
            Log::error('Database restore failed', [
                'backup_id' => $databaseBackup->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.database-backups.index')
                ->with('error', __('pages.backup_restore_failed', ['message' => $e->getMessage()]));
        }
    }

    public function destroy(DatabaseBackup $databaseBackup): RedirectResponse
    {
        try {
            $this->backupService->deleteBackup($databaseBackup);

            return redirect()
                ->route('admin.database-backups.index')
                ->with('status', __('pages.backup_deleted'));
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.database-backups.index')
                ->with('error', $e->getMessage());
        }
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'retain_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $data['enabled'] = $request->boolean('enabled');

        if ($data['frequency'] === 'weekly' && ! isset($data['day_of_week'])) {
            return redirect()
                ->route('admin.database-backups.index')
                ->with('error', __('pages.backup_day_of_week_required'));
        }

        if ($data['frequency'] === 'monthly' && empty($data['day_of_month'])) {
            return redirect()
                ->route('admin.database-backups.index')
                ->with('error', __('pages.backup_day_of_month_required'));
        }

        $this->backupService->updateSettings($data, $request->user()->id);

        return redirect()
            ->route('admin.database-backups.index')
            ->with('status', __('pages.backup_schedule_saved'));
    }
}
