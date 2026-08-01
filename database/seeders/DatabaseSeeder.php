<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CustomerUser;
use App\Models\AdminUser;
use App\Models\MedicalInstitution;
use App\Models\Department;
use App\Models\JobType;
use App\Models\DeliveryLocation;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\UniformProduct;
use App\Models\Contract;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ============================================
        // CREATE MEDICAL INSTITUTION FIRST
        // ============================================
        $institution = MedicalInstitution::firstOrCreate(
            ['code' => 'HOSP-001'],
            [
                'name' => 'テスト総合病院',
                'billing_model' => 'fixed_per_person',
                'notification_email' => 'uniform-admin@example.com',
                'per_person_loan_limit' => 5,
            ]
        );

        // Create Contract
        Contract::firstOrCreate(
            ['medical_institution_id' => $institution->id],
            [
                'contract_type' => 'FIXED',
                'billing_model' => 'fixed_per_person',
                'per_person_loan_limit' => 5,
                'loan_limit_per_item' => 3,
                'fixed_unit_price' => 5000.00,
                'point_unit_price' => null,
                'valid_from' => now()->subYear(),
                'valid_until' => now()->addYear(),
            ]
        );

        // Create Departments
        $department1 = Department::firstOrCreate(
            ['code' => 'DEPT-001'],
            [
                'medical_institution_id' => $institution->id,
                'name' => '外科',
            ]
        );

        $department2 = Department::firstOrCreate(
            ['code' => 'DEPT-002'],
            [
                'medical_institution_id' => $institution->id,
                'name' => '内科',
            ]
        );

        $department3 = Department::firstOrCreate(
            ['code' => 'DEPT-003'],
            [
                'medical_institution_id' => $institution->id,
                'name' => '看護部',
            ]
        );

        // Create Job Types
        $jobType1 = JobType::firstOrCreate(
            ['code' => 'JOB-001'],
            [
                'medical_institution_id' => $institution->id,
                'name' => '医師',
            ]
        );

        $jobType2 = JobType::firstOrCreate(
            ['code' => 'JOB-002'],
            [
                'medical_institution_id' => $institution->id,
                'name' => '看護師',
            ]
        );

        $jobType3 = JobType::firstOrCreate(
            ['code' => 'JOB-003'],
            [
                'medical_institution_id' => $institution->id,
                'name' => '薬剤師',
            ]
        );

        // 納品場所を作成
        DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => '本館 1階 受付',
            'note' => '平日 9:00-17:00',
        ]);

        DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => '別館 2階 倉庫',
            'note' => '平日 10:00-16:00',
        ]);

        DeliveryLocation::create([
            'medical_institution_id' => $institution->id,
            'name' => '中央倉庫',
            'note' => '24時間対応',
        ]);

        // ユニフォーム商品を作成
        $uniforms = [];
        
        // 上衣（男性用）
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $size) {
            $uniforms[] = UniformProduct::create([
                'product_code' => 'TOP-M-' . $size,
                'name' => '医療用上衣（男性用）',
                'category' => 'top',
                'size' => $size,
                'gender' => 'male',
                'stock_quantity' => 50,
                'image_path' => null,
            ]);
        }

        // 上衣（女性用）
        foreach ($sizes as $size) {
            $uniforms[] = UniformProduct::create([
                'product_code' => 'TOP-F-' . $size,
                'name' => '医療用上衣（女性用）',
                'category' => 'top',
                'size' => $size,
                'gender' => 'female',
                'stock_quantity' => 50,
                'image_path' => null,
            ]);
        }

        // ズボン（男性用）
        foreach ($sizes as $size) {
            $uniforms[] = UniformProduct::create([
                'product_code' => 'BOT-M-' . $size,
                'name' => '医療用ズボン（男性用）',
                'category' => 'bottom',
                'size' => $size,
                'gender' => 'male',
                'stock_quantity' => 50,
                'image_path' => null,
            ]);
        }

        // ズボン（女性用）
        foreach ($sizes as $size) {
            $uniforms[] = UniformProduct::create([
                'product_code' => 'BOT-F-' . $size,
                'name' => '医療用ズボン（女性用）',
                'category' => 'bottom',
                'size' => $size,
                'gender' => 'female',
                'stock_quantity' => 50,
                'image_path' => null,
            ]);
        }

        // 靴（ユニセックス）
        $shoeSizes = ['23.0', '23.5', '24.0', '24.5', '25.0', '25.5', '26.0', '26.5', '27.0', '27.5', '28.0'];
        foreach ($shoeSizes as $size) {
            $uniforms[] = UniformProduct::create([
                'product_code' => 'SHOE-' . str_replace('.', '-', $size),
                'name' => '医療用シューズ',
                'category' => 'shoe',
                'size' => $size,
                'gender' => 'unisex',
                'stock_quantity' => 30,
                'image_path' => null,
            ]);
        }

        // Plan-linked products supersede department/job uniform recommendations (SPEC 2026-07-31).

        // 従業員を作成
        $employee1 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10001',
            'name' => '山田 太郎',
            'gender' => 'male',
            'top_size' => 'L',
            'bottom_size' => 'L',
            'shoe_size' => '27.0',
            'status' => 'employed',
            'primary_department_id' => $department1->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee1->id,
            'department_id' => $department1->id,
            'job_type_id' => $jobType1->id,
        ]);

        $employee2 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10002',
            'name' => '佐藤 花子',
            'gender' => 'female',
            'top_size' => 'M',
            'bottom_size' => 'M',
            'shoe_size' => '24.0',
            'status' => 'employed',
            'primary_department_id' => $department3->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee2->id,
            'department_id' => $department3->id,
            'job_type_id' => $jobType2->id,
        ]);

        $employee3 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10003',
            'name' => '鈴木 一郎',
            'gender' => 'male',
            'top_size' => 'XL',
            'bottom_size' => 'XL',
            'shoe_size' => '28.0',
            'status' => 'employed',
            'primary_department_id' => $department2->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee3->id,
            'department_id' => $department2->id,
            'job_type_id' => $jobType1->id,
        ]);

        $employee4 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10004',
            'name' => '田中 美咲',
            'gender' => 'female',
            'top_size' => 'S',
            'bottom_size' => 'S',
            'shoe_size' => '23.0',
            'status' => 'employed',
            'primary_department_id' => $department3->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee4->id,
            'department_id' => $department3->id,
            'job_type_id' => $jobType2->id,
        ]);

        $employee5 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10005',
            'name' => '伊藤 健太',
            'gender' => 'male',
            'top_size' => 'L',
            'bottom_size' => 'L',
            'shoe_size' => '26.5',
            'status' => 'employed',
            'primary_department_id' => $department1->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee5->id,
            'department_id' => $department1->id,
            'job_type_id' => $jobType1->id,
        ]);

        // 追加の従業員
        $employee6 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10006',
            'name' => '高橋 さくら',
            'gender' => 'female',
            'top_size' => 'M',
            'bottom_size' => 'M',
            'shoe_size' => '24.5',
            'status' => 'employed',
            'primary_department_id' => $department3->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee6->id,
            'department_id' => $department3->id,
            'job_type_id' => $jobType2->id,
        ]);

        $employee7 = Employee::create([
            'medical_institution_id' => $institution->id,
            'staff_no' => '10007',
            'name' => '渡辺 雄一',
            'gender' => 'male',
            'top_size' => 'M',
            'bottom_size' => 'M',
            'shoe_size' => '25.5',
            'status' => 'employed',
            'primary_department_id' => $department2->id,
        ]);

        EmployeePosition::create([
            'employee_id' => $employee7->id,
            'department_id' => $department2->id,
            'job_type_id' => $jobType1->id,
        ]);

        // ============================================
        // 6 ROLES - USER ACCOUNTS WITH LOGIN INFO
        // ============================================
        
        // 1. 医療機関 全体管理者 (Institution Admin)
        $institutionAdminUser = User::create([
            'name' => '医療機関 全体管理者',
            'email' => 'hospital-admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'customer_admin',
        ]);
        CustomerUser::create([
            'user_id' => $institutionAdminUser->id,
            'medical_institution_id' => $institution->id,
            'role' => 'institution_admin',
            'department_id' => null,
        ]);

        // 2. 部署管理者 (Department Admin)
        $departmentAdminUser = User::create([
            'name' => '部署管理者',
            'email' => 'department-admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'customer_admin',
        ]);
        CustomerUser::create([
            'user_id' => $departmentAdminUser->id,
            'medical_institution_id' => $institution->id,
            'role' => 'department_admin',
            'department_id' => $department1->id, // 外科
        ]);

        // 3. 鎌谷 システム管理者 (System Admin)
        $systemAdminUser = User::create([
            'name' => '鎌谷 システム管理者',
            'email' => 'kamatani-admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'kamatani_admin',
        ]);
        AdminUser::create([
            'user_id' => $systemAdminUser->id,
            'role' => 'system_admin',
        ]);

        // 4. 運営管理者 (Operations Admin)
        $operationsAdminUser = User::create([
            'name' => '運営管理者',
            'email' => 'operations-admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'kamatani_admin',
        ]);
        AdminUser::create([
            'user_id' => $operationsAdminUser->id,
            'role' => 'operation',
        ]);

        // 5. 営業管理者 (Sales Admin)
        $salesAdminUser = User::create([
            'name' => '営業管理者',
            'email' => 'sales-admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'kamatani_admin',
        ]);
        AdminUser::create([
            'user_id' => $salesAdminUser->id,
            'role' => 'sales',
        ]);

        // 6. 経理管理者 (Accounting Admin)
        $accountingAdminUser = User::create([
            'name' => '経理管理者',
            'email' => 'accounting-admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'kamatani_admin',
        ]);
        AdminUser::create([
            'user_id' => $accountingAdminUser->id,
            'role' => 'accounting',
        ]);

        // 7. 契約プラン管理者 (Contract Plan Manager)
        $planManagerUser = User::create([
            'name' => '契約プラン管理者',
            'email' => 'plan-manager@example.com',
            'password' => Hash::make('password123'),
            'type' => 'kamatani_admin',
        ]);
        AdminUser::create([
            'user_id' => $planManagerUser->id,
            'role' => 'contract_plan_manager',
        ]);

        // Sample contract plan domain for apply eligibility testing
        $maker = \App\Models\Maker::firstOrCreate(
            ['code' => 'MKR-001'],
            ['name' => 'サンプルメーカー', 'is_active' => true]
        );

        $contractPlan = \App\Models\ContractPlan::firstOrCreate(
            ['code' => 'PLAN-5'],
            [
                'name' => '5点リース',
                'rentable_points' => 5,
                'unit_price_ex_tax' => 8000,
                'is_active' => true,
            ]
        );

        $productIds = UniformProduct::whereIn('category', ['top', 'bottom', 'shoe'])->limit(40)->pluck('id');
        if ($productIds->isNotEmpty()) {
            $contractPlan->uniformProducts()->syncWithoutDetaching($productIds->all());
        }

        $ccp = \App\Models\CustomerContractPlan::firstOrCreate(
            [
                'medical_institution_id' => $institution->id,
                'contract_plan_id' => $contractPlan->id,
            ],
            [
                'contract_count' => 10,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addYear(),
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]
        );

        foreach ([$employee1, $employee2, $employee3] as $emp) {
            \App\Models\EmployeeContractPlan::firstOrCreate(
                [
                    'employee_id' => $emp->id,
                    'customer_contract_plan_id' => $ccp->id,
                ],
                [
                    'assigned_from' => now()->subMonth(),
                    'assigned_until' => null,
                ]
            );
        }

        // ============================================
        // LOGIN INFORMATION SUMMARY
        // ============================================
        // 1. Institution Admin: hospital-admin@example.com / password123
        // 2. Department Admin: department-admin@example.com / password123
        // 3. System Admin: kamatani-admin@example.com / password123
        // 4. Operations Admin: operations-admin@example.com / password123
        // 5. Sales Admin: sales-admin@example.com / password123
        // 6. Accounting Admin: accounting-admin@example.com / password123
        // 7. Contract Plan Manager: plan-manager@example.com / password123
    }
}
