<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Employee;
use App\Policies\ApplicationPolicy;
use App\Policies\EmployeePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);

        Gate::define('manage-masters', function ($user) {
            if (!$user) {
                return false;
            }

            $customer = $user->customerUser;
            return $customer && $customer->role === 'institution_admin';
        });

        Gate::define('manage-database', function ($user) {
            $admin = $user?->adminUser;
            return $admin && !$admin->trashed() && !$admin->isBlocked() && $admin->role === 'system_admin';
        });
    }
}
