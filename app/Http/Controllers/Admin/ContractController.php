<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\MedicalInstitution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ContractController extends Controller
{
    /**
     * Check if user has permission to manage contracts (create/edit/delete)
     * Sales managers can fully manage contracts
     */
    private function checkManagePermission()
    {
        $admin = request()->user()->adminUser ?? null;
        if (!$admin || !in_array($admin->role, ['system_admin', 'operation', 'sales'])) {
            abort(403, '契約の作成・編集・削除権限がありません。');
        }
    }

    /**
     * Check if user has permission to view contracts
     */
    private function checkViewPermission()
    {
        $admin = request()->user()->adminUser ?? null;
        if (!$admin || !in_array($admin->role, ['system_admin', 'operation', 'sales'])) {
            abort(403, '契約情報の閲覧権限がありません。');
        }
    }

    public function index(): View
    {
        $this->checkViewPermission();
        
        $contracts = Contract::with('medicalInstitution')
            ->orderByDesc('valid_from')
            ->paginate(20);

        // Calculate statistics
        $totalContracts = Contract::count();
        $activeContracts = Contract::where(function($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now());
        })
        ->where(function($q) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', now());
        })
        ->count();
        
        $expiringSoon = Contract::where('valid_until', '>=', now())
            ->where('valid_until', '<=', now()->addDays(30))
            ->count();
        
        $fixedPriceContracts = Contract::where('contract_type', 'FIXED')->count();
        $pointBasedContracts = Contract::where('contract_type', 'POINT_BASED')->count();

        return view('admin.contracts.index', [
            'contracts' => $contracts,
            'stats' => [
                'total_contracts' => $totalContracts,
                'active_contracts' => $activeContracts,
                'expiring_soon' => $expiringSoon,
                'fixed_price_contracts' => $fixedPriceContracts,
                'point_based_contracts' => $pointBasedContracts,
            ],
        ]);
    }

    public function create(): View
    {
        $this->checkManagePermission();
        
        $institutions = MedicalInstitution::orderBy('name')->get();
        
        return view('admin.contracts.create', [
            'institutions' => $institutions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkManagePermission();
        $rules = [
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'contract_type' => ['required', 'in:FIXED,POINT_BASED'],
            'billing_model' => ['required', 'in:fixed_per_person,point_usage'],
            'per_person_loan_limit' => ['nullable', 'integer', 'min:1'],
            'loan_limit_per_item' => ['nullable', 'integer', 'min:1'],
            'point_unit_price' => ['nullable', 'numeric', 'min:0', 'required_if:billing_model,point_usage'],
            'fixed_unit_price' => ['nullable', 'numeric', 'min:0', 'required_if:billing_model,fixed_per_person'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
        ];

        // Add contract_number validation only if column exists
        if (Schema::hasColumn('contracts', 'contract_number')) {
            $rules['contract_number'] = ['nullable', 'string', 'max:50', 'unique:contracts,contract_number'];
        }

        // Add cleaning_multiplier validation only if column exists
        if (Schema::hasColumn('contracts', 'cleaning_multiplier')) {
            $rules['cleaning_multiplier'] = ['nullable', 'numeric', 'min:0.1', 'max:10'];
        }

        $data = $request->validate($rules);

        // contract_type と billing_model の整合
        if ($data['contract_type'] === 'FIXED' && $data['billing_model'] !== 'fixed_per_person') {
            return back()->withInput()->withErrors([
                'billing_model' => '契約種別が FIXED の場合、請求モデルは fixed_per_person にしてください。',
            ]);
        }
        if ($data['contract_type'] === 'POINT_BASED' && $data['billing_model'] !== 'point_usage') {
            return back()->withInput()->withErrors([
                'billing_model' => '契約種別が POINT_BASED の場合、請求モデルは point_usage にしてください。',
            ]);
        }

        // Set default cleaning_multiplier if not provided and column exists
        if (Schema::hasColumn('contracts', 'cleaning_multiplier')) {
            if (!isset($data['cleaning_multiplier']) || empty($data['cleaning_multiplier'])) {
                $data['cleaning_multiplier'] = 1.67;
            }
        }

        Contract::create($data);

        return redirect()
            ->route('admin.contracts.index')
            ->with('status', '契約を登録しました。');
    }

    public function show(Contract $contract): View
    {
        $this->checkViewPermission();
        
        $contract->load('medicalInstitution');
        
        return view('admin.contracts.show', [
            'contract' => $contract,
        ]);
    }

    public function edit(Contract $contract): View
    {
        $this->checkManagePermission();
        
        $institutions = MedicalInstitution::orderBy('name')->get();
        
        return view('admin.contracts.edit', [
            'contract' => $contract,
            'institutions' => $institutions,
        ]);
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->checkManagePermission();
        $rules = [
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'contract_type' => ['required', 'in:FIXED,POINT_BASED'],
            'billing_model' => ['required', 'in:fixed_per_person,point_usage'],
            'per_person_loan_limit' => ['nullable', 'integer', 'min:1'],
            'loan_limit_per_item' => ['nullable', 'integer', 'min:1'],
            'point_unit_price' => ['nullable', 'numeric', 'min:0', 'required_if:billing_model,point_usage'],
            'fixed_unit_price' => ['nullable', 'numeric', 'min:0', 'required_if:billing_model,fixed_per_person'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
        ];

        // Add contract_number validation only if column exists
        if (Schema::hasColumn('contracts', 'contract_number')) {
            $rules['contract_number'] = ['nullable', 'string', 'max:50', 'unique:contracts,contract_number,' . $contract->id];
        }

        // Add cleaning_multiplier validation only if column exists
        if (Schema::hasColumn('contracts', 'cleaning_multiplier')) {
            $rules['cleaning_multiplier'] = ['nullable', 'numeric', 'min:0.1', 'max:10'];
        }

        $data = $request->validate($rules);

        // contract_type と billing_model の整合
        if ($data['contract_type'] === 'FIXED' && $data['billing_model'] !== 'fixed_per_person') {
            return back()->withInput()->withErrors([
                'billing_model' => '契約種別が FIXED の場合、請求モデルは fixed_per_person にしてください。',
            ]);
        }
        if ($data['contract_type'] === 'POINT_BASED' && $data['billing_model'] !== 'point_usage') {
            return back()->withInput()->withErrors([
                'billing_model' => '契約種別が POINT_BASED の場合、請求モデルは point_usage にしてください。',
            ]);
        }

        // Set default cleaning_multiplier if not provided and column exists
        if (Schema::hasColumn('contracts', 'cleaning_multiplier')) {
            if (!isset($data['cleaning_multiplier']) || empty($data['cleaning_multiplier'])) {
                $data['cleaning_multiplier'] = $contract->cleaning_multiplier ?? 1.67;
            }
        }

        $contract->update($data);

        return redirect()
            ->route('admin.contracts.index')
            ->with('status', '契約を更新しました。');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->checkManagePermission();
        
        $contract->delete();

        return redirect()
            ->route('admin.contracts.index')
            ->with('status', '契約を削除しました。');
    }
}
