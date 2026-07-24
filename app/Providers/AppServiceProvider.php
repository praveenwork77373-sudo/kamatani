<?php

namespace App\Providers;

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
        // Define gate for managing master data (institution admins only)
        Gate::define('manage-masters', function ($user) {
            if (!$user) {
                return false;
            }
            
            $customer = $user->customerUser;
            return $customer && $customer->role === 'institution_admin';
        });
    }
}
