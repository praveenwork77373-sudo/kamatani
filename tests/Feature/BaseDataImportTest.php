<?php

namespace Tests\Feature;

use App\Models\ContractPlan;
use App\Models\CustomerContractPlan;
use App\Models\Maker;
use App\Models\MedicalInstitution;
use App\Models\UniformProduct;
use App\Services\BaseDataImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseDataImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_loads_hospitals_makers_products_and_plans(): void
    {
        $matches = glob(base_path('clientdocs/260805*.xlsx')) ?: [];
        $this->assertNotEmpty($matches, 'Expected clientdocs/260805*.xlsx to exist');

        $result = app(BaseDataImportService::class)->import($matches[0]);

        $this->assertSame(4, Maker::count());
        $this->assertSame(6, MedicalInstitution::count());
        $this->assertGreaterThan(50, UniformProduct::count());
        $this->assertGreaterThan(100, ContractPlan::count());
        $this->assertGreaterThan(100, CustomerContractPlan::count());

        $koyama = MedicalInstitution::where('code', '30170300')->first();
        $this->assertNotNull($koyama);
        $this->assertSame('こやまクリニック', $koyama->name);
        $this->assertSame('91703', $koyama->onoe_code);
        $this->assertSame(1, $koyama->billing_closing_day);
        $this->assertSame('高木', $koyama->sales_person_name);

        $this->assertTrue(Maker::where('code', '3108')->exists());
        $this->assertTrue(
            UniformProduct::where('product_code', '038-28-S')->orWhere('product_code', '038-28')->exists()
        );

        $plan = ContractPlan::where('code', '30170300-R0101')->first();
        $this->assertNotNull($plan);
        $this->assertSame('医師（男女）', $plan->name);
        $this->assertGreaterThan(0, $plan->rentable_points);
        $this->assertTrue($plan->uniformProducts()->exists());

        $link = CustomerContractPlan::where('medical_institution_id', $koyama->id)
            ->where('contract_plan_id', $plan->id)
            ->first();
        $this->assertNotNull($link);
        $this->assertSame('confirmed', $link->status);

        $this->assertArrayHasKey('institutions', $result['stats']);
        $this->assertSame(6, $result['stats']['institutions']);
    }

    public function test_institution_create_accepts_client_code_and_onoe_code(): void
    {
        $admin = \App\Models\User::factory()->create([
            'type' => 'admin',
            'email' => 'base-data-admin@example.com',
        ]);
        \App\Models\AdminUser::create([
            'user_id' => $admin->id,
            'role' => 'system_admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.masters.institutions.store'), [
            'name' => 'テスト病院',
            'code' => '30999900',
            'onoe_code' => '99999',
            'notification_email' => 'test-hospital@example.com',
            'billing_closing_day' => 1,
            'sales_person_name' => '山田',
            'manager_name' => '管理者',
            'manager_email' => 'manager-test@example.com',
            'manager_password' => 'password123',
            'manager_password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.masters.institutions'));

        $institution = MedicalInstitution::where('code', '30999900')->first();
        $this->assertNotNull($institution);
        $this->assertSame('99999', $institution->onoe_code);
    }

    public function test_import_empty_product_size_becomes_f_not_dash(): void
    {
        $matches = glob(base_path('clientdocs/260805*.xlsx')) ?: [];
        $this->assertNotEmpty($matches);

        app(BaseDataImportService::class)->import($matches[0]);

        $this->assertSame(
            0,
            UniformProduct::query()
                ->where('size', '-')
                ->where('name', 'not like', '%(import stub)%')
                ->count(),
            'Non-stub imported products must not use size "-"'
        );

        $freeSized = UniformProduct::query()
            ->where('size', 'F')
            ->where('product_code', 'like', '%-F')
            ->exists();
        $this->assertTrue($freeSized || UniformProduct::where('size', 'F')->exists());
    }
}
