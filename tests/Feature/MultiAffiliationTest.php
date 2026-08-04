<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use App\Models\User;
use App\Services\EmployeeCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiAffiliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_admin_sees_concurrent_affiliate(): void
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-AFF',
            'name' => '兼務病院',
            'notification_email' => 'n@example.com',
            'billing_closing_day' => 1,
        ]);
        $deptA = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'A',
            'name' => '内科',
        ]);
        $deptB = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'B',
            'name' => '外科',
        ]);
        $job = JobType::create([
            'medical_institution_id' => $institution->id,
            'code' => 'J1',
            'name' => '看護師',
        ]);

        $employee = Employee::create([
            'medical_institution_id' => $institution->id,
            'primary_department_id' => $deptA->id,
            'staff_no' => 'AFF001',
            'name' => '兼務太郎',
            'status' => 'employed',
        ]);
        EmployeePosition::create([
            'employee_id' => $employee->id,
            'department_id' => $deptA->id,
            'job_type_id' => $job->id,
        ]);
        EmployeePosition::create([
            'employee_id' => $employee->id,
            'department_id' => $deptB->id,
            'job_type_id' => $job->id,
        ]);

        $user = User::factory()->create(['type' => 'customer']);
        CustomerUser::create([
            'user_id' => $user->id,
            'medical_institution_id' => $institution->id,
            'department_id' => $deptB->id,
            'role' => 'department_admin',
        ]);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('兼務太郎');

        $this->assertTrue($employee->isAffiliatedWithDepartment((int) $deptB->id));
        $this->assertTrue(
            Employee::query()->inDepartment((int) $deptB->id)->whereKey($employee->id)->exists()
        );
    }

    public function test_csv_multi_row_round_trip_creates_positions(): void
    {
        $institution = MedicalInstitution::create([
            'code' => 'H-CSV',
            'name' => 'CSV病院',
            'notification_email' => 'c@example.com',
            'billing_closing_day' => 1,
        ]);
        $deptA = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'A',
            'name' => '内科',
        ]);
        $deptB = Department::create([
            'medical_institution_id' => $institution->id,
            'code' => 'B',
            'name' => '外科',
        ]);
        JobType::create([
            'medical_institution_id' => $institution->id,
            'code' => 'J1',
            'name' => '看護師',
        ]);
        JobType::create([
            'medical_institution_id' => $institution->id,
            'code' => 'J2',
            'name' => '技師',
        ]);

        $csv = app(EmployeeCsvService::class);
        $header = $csv->headers();
        $rows = [
            ['E200', '山田', '女性', 'M', 'M', '', '', '', '内科', '看護師', 'Y', '', '', '在籍', ''],
            ['E200', '山田', '女性', 'M', 'M', '', '', '', '外科', '技師', 'N', '', '', '在籍', ''],
        ];

        $result = $csv->importRows($header, $rows, (int) $institution->id, null);

        $this->assertSame(1, $result['imported']);
        $employee = Employee::where('staff_no', 'E200')->first();
        $this->assertNotNull($employee);
        $this->assertSame((int) $deptA->id, (int) $employee->primary_department_id);
        $this->assertSame(2, $employee->positions()->count());
        $this->assertTrue($employee->isAffiliatedWithDepartment((int) $deptB->id));
    }
}
