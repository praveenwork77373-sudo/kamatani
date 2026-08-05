<?php

use App\Http\Controllers\Admin\ApplicationApprovalController;
use App\Http\Controllers\Admin\UniformProductController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InstitutionLookupController;

use Illuminate\Support\Facades\Route;

// 認証（病院 / カマタニ — URLで分離、選択ページなし）
Route::middleware('guest')->group(function () {
    Route::redirect('/', '/login')->name('home');

    // Hospital (customer) login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.post');

    // Kamatani admin login
    Route::get('/admin/login', [AuthController::class, 'showAdminLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:20,1')->name('admin.login.post');

    Route::get('/password/forgot', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
});

    Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password/force-change', [AuthController::class, 'showForceChangePasswordForm'])->name('password.force-change');
    Route::post('/password/force-change', [AuthController::class, 'updateForcedPassword'])->name('password.force-change.update');
});

// 言語切り替え
Route::get('/language/{locale}', [\App\Http\Controllers\LanguageController::class, 'switch'])->name('language.switch');

// 共通ダッシュボード
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 通知
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])
        ->whereNumber('notification')
        ->name('notifications.read');
    Route::get('/api/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('api.notifications.unread_count');
    Route::get('/api/master-code/check', [\App\Http\Controllers\Api\MasterCodeController::class, 'check'])->name('api.master_code.check');
    Route::get('/api/institution/{institution}/departments-job-types', [InstitutionLookupController::class, 'departmentsJobTypes'])
        ->name('api.institution.departments_job_types');
    Route::get('/api/departments', [InstitutionLookupController::class, 'departments'])
        ->name('api.departments');
});

// 医療機関側（病院ポータル）
Route::middleware(['auth', 'customer'])->group(function () {
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('/employees/export-csv', [EmployeeController::class, 'exportCsv'])->name('employees.export_csv');
    Route::post('/employees/import-csv', [EmployeeController::class, 'importCsv'])->name('employees.import_csv');
    Route::get('/loan-status', [\App\Http\Controllers\LoanStatusController::class, 'index'])->name('loan-status.index');
    Route::get('/departments/{department}/loans', [\App\Http\Controllers\Customer\MasterController::class, 'departmentShow'])->name('departments.loans');
    Route::resource('applications', ApplicationController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/applications/{application}/request-cancel', [ApplicationController::class, 'requestCancel'])->name('applications.request_cancel');
    Route::get('/api/uniforms-by-plan', [ApplicationController::class, 'getUniformsByPlan'])->name('api.uniforms_by_plan');
    Route::get('/api/employees-by-department', [ApplicationController::class, 'getEmployeesByDepartment'])->name('api.employees_by_department');
    Route::get('/api/employees/search', [ApplicationController::class, 'searchEmployees'])->name('api.employees.search');
    Route::get('/api/employees/{employee}/loans', [ApplicationController::class, 'getEmployeeLoans'])->name('api.employees.loans');

    // 医療機関側 マスタ管理（全体管理者のみ）
    Route::prefix('customer/masters')->name('customer.masters.')->middleware('can:manage-masters')->group(function () {
        Route::get('/departments', [\App\Http\Controllers\Customer\MasterController::class, 'departments'])->name('departments');
        Route::get('/departments/create', [\App\Http\Controllers\Customer\MasterController::class, 'departmentCreate'])->name('departments.create');
        Route::post('/departments', [\App\Http\Controllers\Customer\MasterController::class, 'departmentStore'])->name('departments.store');
        Route::get('/departments/{department}', [\App\Http\Controllers\Customer\MasterController::class, 'departmentShow'])->name('departments.show');
        Route::get('/departments/{department}/edit', [\App\Http\Controllers\Customer\MasterController::class, 'departmentEdit'])->name('departments.edit');
        Route::put('/departments/{department}', [\App\Http\Controllers\Customer\MasterController::class, 'departmentUpdate'])->name('departments.update');
        Route::delete('/departments/{department}', [\App\Http\Controllers\Customer\MasterController::class, 'departmentDestroy'])->name('departments.destroy');
        
        Route::get('/job-types', [\App\Http\Controllers\Customer\MasterController::class, 'jobTypes'])->name('job-types');
        Route::get('/job-types/create', [\App\Http\Controllers\Customer\MasterController::class, 'jobTypeCreate'])->name('job-types.create');
        Route::post('/job-types', [\App\Http\Controllers\Customer\MasterController::class, 'jobTypeStore'])->name('job-types.store');
        Route::get('/job-types/{jobType}/edit', [\App\Http\Controllers\Customer\MasterController::class, 'jobTypeEdit'])->name('job-types.edit');
        Route::put('/job-types/{jobType}', [\App\Http\Controllers\Customer\MasterController::class, 'jobTypeUpdate'])->name('job-types.update');
        Route::delete('/job-types/{jobType}', [\App\Http\Controllers\Customer\MasterController::class, 'jobTypeDestroy'])->name('job-types.destroy');
        
        Route::get('/delivery-locations', [\App\Http\Controllers\Customer\MasterController::class, 'deliveryLocations'])->name('delivery-locations');
        Route::get('/delivery-locations/create', [\App\Http\Controllers\Customer\MasterController::class, 'deliveryLocationCreate'])->name('delivery-locations.create');
        Route::post('/delivery-locations', [\App\Http\Controllers\Customer\MasterController::class, 'deliveryLocationStore'])->name('delivery-locations.store');
        Route::get('/delivery-locations/{deliveryLocation}/edit', [\App\Http\Controllers\Customer\MasterController::class, 'deliveryLocationEdit'])->name('delivery-locations.edit');
        Route::put('/delivery-locations/{deliveryLocation}', [\App\Http\Controllers\Customer\MasterController::class, 'deliveryLocationUpdate'])->name('delivery-locations.update');
        Route::delete('/delivery-locations/{deliveryLocation}', [\App\Http\Controllers\Customer\MasterController::class, 'deliveryLocationDestroy'])->name('delivery-locations.destroy');
        
        Route::get('/department-admins', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'index'])->name('department-admins');
        Route::get('/department-admins/create', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'create'])->name('department-admins.create');
        Route::post('/department-admins', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'store'])->name('department-admins.store');
        Route::get('/department-admins/{customerUser}/edit', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'edit'])->name('department-admins.edit');
        Route::put('/department-admins/{customerUser}', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'update'])->name('department-admins.update');
        Route::delete('/department-admins/{customerUser}', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'destroy'])->name('department-admins.destroy');
    });

    Route::get('/customer/contract-plans', [\App\Http\Controllers\Customer\ContractPlanController::class, 'index'])
        ->name('customer.contract-plans.index');
});

// 鎌谷側 管理ポータル
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::middleware('admin.role:system_admin,operation')->group(function () {
            Route::get('/applications', [ApplicationApprovalController::class, 'index'])->name('applications.index');
            Route::get('/applications/export', [ApplicationApprovalController::class, 'exportCsv'])->name('applications.export');
            Route::get('/applications/{application}', [ApplicationApprovalController::class, 'show'])->name('applications.show');
            Route::post('/applications/{application}/memos', [ApplicationApprovalController::class, 'updateMemos'])->name('applications.update_memos');
            Route::post('/applications/{application}/approve', [ApplicationApprovalController::class, 'approve'])->name('applications.approve');
            Route::post('/applications/{application}/complete', [ApplicationApprovalController::class, 'complete'])->name('applications.complete');
            Route::post('/applications/{application}/reject', [ApplicationApprovalController::class, 'reject'])->name('applications.reject');
            Route::post('/applications/{application}/cancel-approve', [ApplicationApprovalController::class, 'approveCancel'])->name('applications.cancel_approve');
            Route::post('/applications/{application}/cancel-reject', [ApplicationApprovalController::class, 'rejectCancel'])->name('applications.cancel_reject');
            Route::post('/applications/{application}/documents/issue', [\App\Http\Controllers\Admin\ApplicationDocumentController::class, 'issueAll'])->name('applications.documents.issue');
            Route::post('/applications/documents/bulk-issue', [\App\Http\Controllers\Admin\ApplicationDocumentController::class, 'bulkIssue'])->name('applications.documents.bulk_issue');
            Route::post('/applications/{application}/documents/{formType}/reissue', [\App\Http\Controllers\Admin\ApplicationDocumentController::class, 'reissue'])->name('applications.documents.reissue');
            Route::get('/application-documents/{document}/download', [\App\Http\Controllers\Admin\ApplicationDocumentController::class, 'download'])->name('application-documents.download');
            Route::get('/application-documents/{document}/preview', [\App\Http\Controllers\Admin\ApplicationDocumentController::class, 'preview'])->name('application-documents.preview');
            Route::get('/orders', [ApplicationApprovalController::class, 'orders'])->name('orders.index');
            Route::get('/loan-status', [\App\Http\Controllers\Admin\LoanStatusController::class, 'index'])->name('loan-status.index');
            Route::get('/departments/{department}/loans', [\App\Http\Controllers\Admin\MasterController::class, 'departmentShow'])->name('departments.loans');

            Route::resource('uniform-products', UniformProductController::class);
            Route::get('/uniform-products-export', [UniformProductController::class, 'exportCsv'])->name('uniform-products.export');
            Route::resource('uniform-recommendations', \App\Http\Controllers\Admin\UniformRecommendationController::class)->except(['show']);
        });
        
        // 管理者ユーザー管理 (System Admin Only)
        Route::prefix('admin-users')->name('admin-users.')->middleware('admin.role:system_admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('store');
            Route::get('/{adminUser}/edit', [\App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{adminUser}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('update');
            Route::post('/{adminUser}/block', [\App\Http\Controllers\Admin\AdminUserController::class, 'block'])->name('block');
            Route::post('/{adminUser}/unblock', [\App\Http\Controllers\Admin\AdminUserController::class, 'unblock'])->name('unblock');
            Route::delete('/{adminUser}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [\App\Http\Controllers\Admin\AdminUserController::class, 'restore'])->name('restore');
        });

        // 一時ログインロック解除
        Route::prefix('account-locks')->name('account-locks.')->middleware('admin.role:system_admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AccountLockController::class, 'index'])->name('index');
            Route::post('/{user}/unlock', [\App\Http\Controllers\Admin\AccountLockController::class, 'unlock'])->name('unlock');
        });
        
        // マスタ管理
        Route::prefix('masters')->name('masters.')->middleware('admin.role:system_admin')->group(function () {
            Route::get('/institutions', [\App\Http\Controllers\Admin\MasterController::class, 'institutions'])->name('institutions');
            Route::get('/institutions/create', [\App\Http\Controllers\Admin\MasterController::class, 'institutionCreate'])->name('institutions.create');
            Route::post('/institutions', [\App\Http\Controllers\Admin\MasterController::class, 'institutionStore'])->name('institutions.store');
            Route::get('/institutions/{institution}/edit', [\App\Http\Controllers\Admin\MasterController::class, 'institutionEdit'])->name('institutions.edit');
            Route::put('/institutions/{institution}', [\App\Http\Controllers\Admin\MasterController::class, 'institutionUpdate'])->name('institutions.update');
            Route::delete('/institutions/{institution}', [\App\Http\Controllers\Admin\MasterController::class, 'institutionDestroy'])->name('institutions.destroy');
            Route::post('/institutions/{institution}/welcome-email', [\App\Http\Controllers\Admin\MasterController::class, 'sendWelcomeEmail'])->name('institutions.welcome_email');
            Route::get('/institutions/{institution}/welcome-email-history', [\App\Http\Controllers\Admin\MasterController::class, 'welcomeEmailHistory'])->name('institutions.welcome_email_history');
            
            Route::get('/departments', [\App\Http\Controllers\Admin\MasterController::class, 'departments'])->name('departments');
            Route::get('/departments/create', [\App\Http\Controllers\Admin\MasterController::class, 'departmentCreate'])->name('departments.create');
            Route::post('/departments', [\App\Http\Controllers\Admin\MasterController::class, 'departmentStore'])->name('departments.store');
            Route::get('/departments/{department}/edit', [\App\Http\Controllers\Admin\MasterController::class, 'departmentEdit'])->name('departments.edit');
            Route::put('/departments/{department}', [\App\Http\Controllers\Admin\MasterController::class, 'departmentUpdate'])->name('departments.update');
            Route::delete('/departments/{department}', [\App\Http\Controllers\Admin\MasterController::class, 'departmentDestroy'])->name('departments.destroy');
            
            Route::get('/job-types', [\App\Http\Controllers\Admin\MasterController::class, 'jobTypes'])->name('job-types');
            Route::get('/job-types/create', [\App\Http\Controllers\Admin\MasterController::class, 'jobTypeCreate'])->name('job-types.create');
            Route::post('/job-types', [\App\Http\Controllers\Admin\MasterController::class, 'jobTypeStore'])->name('job-types.store');
            Route::get('/job-types/{jobType}/edit', [\App\Http\Controllers\Admin\MasterController::class, 'jobTypeEdit'])->name('job-types.edit');
            Route::put('/job-types/{jobType}', [\App\Http\Controllers\Admin\MasterController::class, 'jobTypeUpdate'])->name('job-types.update');
            Route::delete('/job-types/{jobType}', [\App\Http\Controllers\Admin\MasterController::class, 'jobTypeDestroy'])->name('job-types.destroy');
            
            Route::get('/delivery-locations', [\App\Http\Controllers\Admin\MasterController::class, 'deliveryLocations'])->name('delivery-locations');
            Route::get('/delivery-locations/create', [\App\Http\Controllers\Admin\MasterController::class, 'deliveryLocationCreate'])->name('delivery-locations.create');
            Route::post('/delivery-locations', [\App\Http\Controllers\Admin\MasterController::class, 'deliveryLocationStore'])->name('delivery-locations.store');
            Route::get('/delivery-locations/{deliveryLocation}/edit', [\App\Http\Controllers\Admin\MasterController::class, 'deliveryLocationEdit'])->name('delivery-locations.edit');
            Route::put('/delivery-locations/{deliveryLocation}', [\App\Http\Controllers\Admin\MasterController::class, 'deliveryLocationUpdate'])->name('delivery-locations.update');
            Route::delete('/delivery-locations/{deliveryLocation}', [\App\Http\Controllers\Admin\MasterController::class, 'deliveryLocationDestroy'])->name('delivery-locations.destroy');
        });
        
        // 契約プラン中心ドメイン（SPEC 2026-07-31）
        // 閲覧: 営業含む / 書込: システム管理者・契約プラン管理のみ
        Route::middleware('admin.role:system_admin,contract_plan_manager,sales')->group(function () {
            Route::get('makers', [\App\Http\Controllers\Admin\MakerController::class, 'index'])->name('makers.index');
            Route::get('contract-plans', [\App\Http\Controllers\Admin\ContractPlanController::class, 'index'])->name('contract-plans.index');
            // whereNumber so "create" is not captured as {contract_plan} before the write-group resource routes
            Route::get('contract-plans/{contract_plan}', [\App\Http\Controllers\Admin\ContractPlanController::class, 'show'])
                ->whereNumber('contract_plan')
                ->name('contract-plans.show');
            Route::get('customer-contract-plans', [\App\Http\Controllers\Admin\CustomerContractPlanController::class, 'index'])->name('customer-contract-plans.index');
            Route::get('uniform-categories', [\App\Http\Controllers\Admin\UniformCategoryController::class, 'index'])->name('uniform-categories.index');
        });

        Route::middleware('admin.role:system_admin,contract_plan_manager')->group(function () {
            Route::resource('makers', \App\Http\Controllers\Admin\MakerController::class)->except(['show', 'index']);
            Route::resource('uniform-categories', \App\Http\Controllers\Admin\UniformCategoryController::class)->except(['show', 'index']);
            Route::resource('contract-plans', \App\Http\Controllers\Admin\ContractPlanController::class)->except(['index', 'show']);
            Route::resource('customer-contract-plans', \App\Http\Controllers\Admin\CustomerContractPlanController::class)->except(['show', 'index']);
            Route::post('/customer-contract-plans/{customerContractPlan}/confirm', [\App\Http\Controllers\Admin\CustomerContractPlanController::class, 'confirm'])
                ->name('customer-contract-plans.confirm');
        });
        
        // 請求データ: 生成は経理・システム管理者のみ。閲覧・Export は営業・運営も可
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::middleware('admin.role:system_admin,accounting')->group(function () {
                Route::get('/create', [\App\Http\Controllers\Admin\BillingController::class, 'create'])->name('create');
                Route::post('/generate', [\App\Http\Controllers\Admin\BillingController::class, 'generate'])->name('generate');
            });
            Route::middleware('admin.role:system_admin,operation,accounting,sales')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\BillingController::class, 'index'])->name('index');
                Route::get('/history', [\App\Http\Controllers\Admin\BillingController::class, 'history'])->name('history');
                Route::get('/{snapshot}/export', [\App\Http\Controllers\Admin\BillingController::class, 'export'])->name('export');
                Route::get('/{snapshot}', [\App\Http\Controllers\Admin\BillingController::class, 'show'])->name('show');
            });
        });
        
        // システム管理者: 全職員管理（運営は貸与状況からの詳細閲覧のみ可）
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::middleware('admin.role:system_admin')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\EmployeeManagementController::class, 'index'])->name('index');
                Route::post('/export-csv', [\App\Http\Controllers\Admin\EmployeeManagementController::class, 'exportCsv'])->name('export_csv');
                Route::post('/import-csv', [\App\Http\Controllers\Admin\EmployeeManagementController::class, 'importCsv'])->name('import_csv');
            });
            Route::middleware('admin.role:system_admin,operation')->group(function () {
                Route::get('/{employee}', [\App\Http\Controllers\Admin\EmployeeManagementController::class, 'show'])->name('show');
            });
        });

        // システム管理者: データベースバックアップ
        Route::prefix('database-backups')->name('database-backups.')->middleware('admin.role:system_admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'store'])->name('store');
            Route::post('/schedule', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'updateSchedule'])->name('schedule');
            Route::get('/{databaseBackup}/download', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'download'])->name('download');
            Route::post('/{databaseBackup}/restore', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'restore'])->name('restore');
            Route::delete('/{databaseBackup}', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'destroy'])->name('destroy');
        });
    });

