<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Services\ApplicationPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicationDocumentController extends Controller
{
    private function ensureApprover(Request $request): AdminUser
    {
        $admin = $request->user()->adminUser;

        if (! $admin || ! in_array($admin->role, ['system_admin', 'operation'], true)) {
            abort(403, '帳票操作の権限がありません。');
        }

        return $admin;
    }

    public function issueAll(Request $request, Application $application, ApplicationPdfService $pdfs): RedirectResponse
    {
        $this->ensureApprover($request);

        if (! in_array($application->status, [
            Application::STATUS_IN_PROGRESS,
            Application::STATUS_COMPLETED,
        ], true)) {
            return back()->with('error', '対応中または完了の申請のみ帳票を発行できます。');
        }

        $pdfs->issueAll($application, $request->user());

        return back()->with('status', '帳票PDF（5種）を発行しました。');
    }

    public function reissue(Request $request, Application $application, string $formType, ApplicationPdfService $pdfs): RedirectResponse
    {
        $this->ensureApprover($request);

        if (! in_array($formType, ApplicationDocument::TYPES, true)) {
            return back()->with('error', '帳票種別が不正です。');
        }

        $doc = $pdfs->issue($application, $formType, $request->user());

        return back()->with('status', $doc->label().' を再発行しました（v'.$doc->version.'）。');
    }

    public function download(Request $request, ApplicationDocument $document): BinaryFileResponse
    {
        $this->ensureApprover($request);

        $path = app(ApplicationPdfService::class)->absolutePath($document);
        if (! is_file($path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        return response()->download(
            $path,
            $document->original_name ?: basename($document->file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function preview(Request $request, ApplicationDocument $document): Response
    {
        $this->ensureApprover($request);

        $path = app(ApplicationPdfService::class)->absolutePath($document);
        if (! is_file($path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($document->original_name ?: 'document.pdf').'"',
        ]);
    }
}
