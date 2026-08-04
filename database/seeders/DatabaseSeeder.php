<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds Kamatani admin login accounts only.
 * No mock hospital, products, plans, or employees — configure those in the UI for a fresh E2E.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production') && ! env('SEED_ADMIN_PASSWORD')) {
            throw new \RuntimeException(
                'Refusing to seed default passwords in production. Set SEED_ADMIN_PASSWORD in the environment.'
            );
        }

        $plain = env('SEED_ADMIN_PASSWORD', 'password123');
        $password = Hash::make($plain);

        $admins = [
            [
                'name' => '鎌谷 システム管理者',
                'email' => 'kamatani-admin@example.com',
                'role' => 'system_admin',
            ],
            [
                'name' => '運営管理者',
                'email' => 'operations-admin@example.com',
                'role' => 'operation',
            ],
            [
                'name' => '営業管理者',
                'email' => 'sales-admin@example.com',
                'role' => 'sales',
            ],
            [
                'name' => '経理管理者',
                'email' => 'accounting-admin@example.com',
                'role' => 'accounting',
            ],
            [
                'name' => '契約プラン管理者',
                'email' => 'plan-manager@example.com',
                'role' => 'contract_plan_manager',
            ],
        ];

        foreach ($admins as $admin) {
            $user = User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => $password,
                    'type' => 'kamatani_admin',
                ]
            );

            AdminUser::firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $admin['role']]
            );
        }

        // Hospital users are created when you register an institution in the UI
        // (Master data → Institutions → create includes institution admin account).
        //
        // Login (default password: password123 — change in production via SEED_ADMIN_PASSWORD):
        // - kamatani-admin@example.com      → /admin/login  (system_admin)
        // - operations-admin@example.com    → /admin/login  (operation)
        // - sales-admin@example.com         → /admin/login  (sales)
        // - accounting-admin@example.com    → /admin/login  (accounting)
        // - plan-manager@example.com        → /admin/login  (contract_plan_manager)
    }
}
