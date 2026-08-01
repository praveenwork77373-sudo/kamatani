<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminUser::class,
            'admin.role' => \App\Http\Middleware\EnsureAdminRole::class,
            'customer' => \App\Http\Middleware\EnsureCustomerUser::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
