<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Application;
use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MedicalInstitution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationAndApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function seedInstitution(): array
    {
        $institution = MedicalInstitution::create([
            'code' => 'T-001',
            'name' => 'テスト病院',
            'notification_email' => 'notify@example.com',
            'billing_closing_day' => 1,
        ]);
        $department = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'D-001',
            'name' => '内科',
        ]);
        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'primary_department_id' => $department->id,
            'staff_no' => 'E001',
            'name' => 'テスト職員',
            'status' => 'employed',
        ]);

        return compact('institution', 'department', 'employee');
    }

    private function makeCustomer(MedicalInstitution $institution, ?Department $department = null, string $role = 'institution_admin'): User
    {
        $user = User::factory()->create([
            'email' => 'customer-'.uniqid().'@example.com',
            'type' => 'customer',
        ]);
        CustomerUser::create([
            'user_id' => $user->id,
            'medical_institution_id' => $institution->id,
            'department_id' => $department?->id,
            'role' => $role,
        ]);
        return $user;
    }

    private function makeAdmin(string $role = 'system_admin'): User
    {
        $user = User::factory()->create([
            'email' => 'admin-'.uniqid().'@example.com',
            'type' => 'admin',
        ]);
        AdminUser::create([
            'user_id' => $user->id,
            'role' => $role,
        ]);
        return $user;
    }

    public function test_customer_cannot_access_admin_uniform_products(): void
    {
        ['institution' => $institution] = $this->seedInstitution();
        $customer = $this->makeCustomer($institution);

        $this->actingAs($customer)
            ->get(route('admin.uniform-products.index'))
            ->assertForbidden();
    }

    public function test_system_admin_can_access_database_backups(): void
    {
        $admin = $this->makeAdmin('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.database-backups.index'))
            ->assertOk();
    }

    public function test_customer_cannot_view_other_institution_application(): void
    {
        ['institution' => $instA, 'department' => $deptA, 'employee' => $empA] = $this->seedInstitution();

        $instB = MedicalInstitution::create([
            'code' => 'T-002',
            'name' => '別病院',
            'notification_email' => 'notify-b@example.com',
            'billing_closing_day' => 1,
        ]);
        $customerB = $this->makeCustomer($instB);

        $requester = $this->makeCustomer($instA);
        $application = Application::create([
            'medical_institution_id' => $instA->id,
            'department_id' => $deptA->id,
            'employee_id' => $empA->id,
            'type' => 'new_loan',
            'status' => 'pending',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'requested_by_user_id' => $requester->id,
        ]);

        $this->actingAs($customerB)
            ->get(route('applications.show', $application))
            ->assertForbidden();
    }

    public function test_blocked_admin_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => bcrypt('password123'),
            'type' => 'admin',
        ]);
        AdminUser::create([
            'user_id' => $user->id,
            'role' => 'system_admin',
            'blocked_at' => now(),
            'blocked_reason' => 'test',
        ]);

        $this->post(route('login.post'), [
            'email' => 'blocked@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
