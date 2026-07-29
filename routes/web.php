<?php

use App\Http\Controllers\Admin\ApplicationApprovalController;
use App\Http\Controllers\Admin\UniformProductController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InstitutionLookupController;

use Illuminate\Support\Facades\Route;

// 認証
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/password/forgot', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// 言語切り替え
Route::get('/language/{locale}', [\App\Http\Controllers\LanguageController::class, 'switch'])->name('language.switch');

// 共通ダッシュボード
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 通知
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::get('/api/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('api.notifications.unread_count');

    // 医療機関側（職員管理・申請）
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('/employees/export-csv', [EmployeeController::class, 'exportCsv'])->name('employees.export_csv');
    Route::post('/employees/import-csv', [EmployeeController::class, 'importCsv'])->name('employees.import_csv');
    Route::resource('applications', ApplicationController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/applications/{application}/request-cancel', [ApplicationController::class, 'requestCancel'])->name('applications.request_cancel');
    Route::get('/api/uniforms-by-dept-job', [ApplicationController::class, 'getUniformsByDeptJob'])->name('api.uniforms_by_dept_job');
    Route::get('/api/employees-by-department', [ApplicationController::class, 'getEmployeesByDepartment'])->name('api.employees_by_department');
    Route::get('/api/employees/search', [ApplicationController::class, 'searchEmployees'])->name('api.employees.search');
    Route::get('/api/employees/{employee}/loans', [ApplicationController::class, 'getEmployeeLoans'])->name('api.employees.loans');
    Route::get('/api/institution/{institution}/departments-job-types', [InstitutionLookupController::class, 'departmentsJobTypes'])
        ->name('api.institution.departments_job_types');
    Route::get('/api/departments', function(\Illuminate\Http\Request $request) {
        $institutionId = $request->input('medical_institution_id');
        if (!$institutionId) {
            return response()->json([]);
        }
        $departments = \App\Models\Department::where('medical_institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($departments);
    })->name('api.departments');



    
    // 医療機関側 マスタ管理（全体管理者のみ）
    Route::prefix('customer/masters')->name('customer.masters.')->middleware('can:manage-masters')->group(function () {
        Route::get('/departments', [\App\Http\Controllers\Customer\MasterController::class, 'departments'])->name('departments');
        Route::get('/departments/create', [\App\Http\Controllers\Customer\MasterController::class, 'departmentCreate'])->name('departments.create');
        Route::post('/departments', [\App\Http\Controllers\Customer\MasterController::class, 'departmentStore'])->name('departments.store');
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
        
        // Department Admin Management
        Route::get('/department-admins', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'index'])->name('department-admins');
        Route::get('/department-admins/create', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'create'])->name('department-admins.create');
        Route::post('/department-admins', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'store'])->name('department-admins.store');
        Route::get('/department-admins/{customerUser}/edit', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'edit'])->name('department-admins.edit');
        Route::put('/department-admins/{customerUser}', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'update'])->name('department-admins.update');
        Route::delete('/department-admins/{customerUser}', [\App\Http\Controllers\Customer\DepartmentAdminController::class, 'destroy'])->name('department-admins.destroy');
    });
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
            Route::post('/applications/{application}/approve', [ApplicationApprovalController::class, 'approve'])->name('applications.approve');
            Route::post('/applications/{application}/reject', [ApplicationApprovalController::class, 'reject'])->name('applications.reject');
            Route::post('/applications/{application}/cancel-approve', [ApplicationApprovalController::class, 'approveCancel'])->name('applications.cancel_approve');
            Route::post('/applications/{application}/cancel-reject', [ApplicationApprovalController::class, 'rejectCancel'])->name('applications.cancel_reject');

            Route::resource('uniform-products', UniformProductController::class);
        });
        
        // 管理者ユーザー管理 (System Admin Only)
        Route::prefix('admin-users')->name('admin-users.')->middleware('admin.role:system_admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('store');
            Route::get('/{adminUser}/edit', [\App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{adminUser}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('update');
            Route::post('/{adminUser}/send-password-reset', [\App\Http\Controllers\Admin\AdminUserController::class, 'sendPasswordReset'])->name('send-password-reset');
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
        
        // 契約管理
        Route::middleware('admin.role:system_admin,operation,sales')->group(function () {
            Route::resource('contracts', \App\Http\Controllers\Admin\ContractController::class)->except(['show']);
            Route::get('/contracts/{contract}', [\App\Http\Controllers\Admin\ContractController::class, 'show'])->name('contracts.show');
        });
        
        // 請求データ
        Route::prefix('billing')->name('billing.')->middleware('admin.role:system_admin,operation,accounting,sales')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BillingController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\BillingController::class, 'create'])->name('create');
            Route::post('/generate', [\App\Http\Controllers\Admin\BillingController::class, 'generate'])->name('generate');
            Route::post('/applications/{application}/approve', [\App\Http\Controllers\Admin\BillingController::class, 'approve'])->name('approve')->where('application', '[0-9]+');
            Route::get('/{snapshot}', [\App\Http\Controllers\Admin\BillingController::class, 'show'])->name('show');
        });
        
        // システム管理者: 全職員管理
        Route::prefix('employees')->name('employees.')->middleware('admin.role:system_admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EmployeeManagementController::class, 'index'])->name('index');
            Route::get('/{employee}', [\App\Http\Controllers\Admin\EmployeeManagementController::class, 'show'])->name('show');
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

