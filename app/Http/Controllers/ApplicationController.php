<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationItem;
use App\Models\CustomerUser;
use App\Models\DeliveryLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\JobType;
use App\Models\UniformProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Support\Pagination;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var CustomerUser|null $customer */
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403);
        }

        $query = Application::with(['employee', 'department'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->orderByDesc('created_at');

        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->where('department_id', $customer->department_id);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(Pagination::perPage($request))->withQueryString();

        return view('applications.index', [
            'applications' => $applications,
            'customer' => $customer,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        /** @var CustomerUser|null $customer */
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403);
        }

        $query = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'employed')
            ->orderBy('staff_no');

        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->where('primary_department_id', $customer->department_id);
        }

        $employees = $query->get();

        $deliveryLocations = DeliveryLocation::where('medical_institution_id', $customer->medical_institution_id)
            ->orderBy('name')
            ->get();

        $departments = Department::where('medical_institution_id', $customer->medical_institution_id)->get();
        $jobTypes = JobType::where('medical_institution_id', $customer->medical_institution_id)->get();
        $uniforms = UniformProduct::orderBy('product_code')->get();

        // Get all possible sizes from database for each category
        // For tops/bottoms: letter sizes (XS, S, M, L, XL, XXL) - stored as strings in database
        // For shoes: numeric sizes (23.0, 23.5, 24.0, etc.) - stored as strings in database
        $topBottomSizes = UniformProduct::whereIn('category', ['top', 'bottom'])
            ->select('size')
            ->distinct()
            ->orderByRaw("CASE size 
                WHEN 'XS' THEN 1 
                WHEN 'S' THEN 2 
                WHEN 'M' THEN 3 
                WHEN 'L' THEN 4 
                WHEN 'XL' THEN 5 
                WHEN 'XXL' THEN 6 
                ELSE 99 
            END")
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        $shoeSizes = UniformProduct::where('category', 'shoe')
            ->select('size')
            ->distinct()
            ->orderByRaw('CAST(size AS DECIMAL(4,1))')
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        // Group uniforms by base product_code (extract base code by removing size suffix)
        // Products are stored as BASE_CODE-SIZE (e.g., "TOP-F-1-M" where "TOP-F-1" is base, "M" is size)
        $uniformsGrouped = $uniforms->groupBy(function($product) {
            // Extract base product_code by removing size suffix
            // Format: BASE_CODE-SIZE, so remove last dash and everything after
            $code = $product->product_code;
            $lastDashPos = strrpos($code, '-');
            if ($lastDashPos !== false) {
                // Check if the part after the last dash matches the size field
                $suffix = substr($code, $lastDashPos + 1);
                if ($suffix === $product->size || in_array($suffix, ['XS', 'S', 'M', 'L', 'XL', 'XXL']) || is_numeric($suffix)) {
                    return substr($code, 0, $lastDashPos);
                }
            }
            return $code;
        })->map(function($group) use ($topBottomSizes, $shoeSizes) {
            $first = $group->first();
            $availableSizes = $group->pluck('size')->unique()->sort()->values()->all();
            $totalStock = $group->sum('stock_quantity');
            
            // Determine which size list to use based on category
            $allSizes = $first->category === 'shoe' ? $shoeSizes : $topBottomSizes;
            
            // Create a map of size => product_id and stock_quantity for this base product_code
            $sizeToIdMap = [];
            $sizeToStockMap = [];
            foreach ($group as $product) {
                if ($product->size) {
                    $sizeToIdMap[$product->size] = $product->id;
                    // Sum stock quantities if multiple products have the same size
                    if (!isset($sizeToStockMap[$product->size])) {
                        $sizeToStockMap[$product->size] = 0;
                    }
                    $sizeToStockMap[$product->size] += ($product->stock_quantity ?? 0);
                }
            }
            
            // Create sizes array with product IDs and stock quantities
            $sizesWithIds = [];
            foreach ($allSizes as $size) {
                $productId = $sizeToIdMap[$size] ?? null;
                $stockQty = $sizeToStockMap[$size] ?? 0;
                $sizesWithIds[] = [
                    'size' => $size,
                    'product_id' => $productId,
                    'stock_quantity' => $stockQty,
                    'available' => $productId !== null && $stockQty > 0,
                ];
            }
            
            // Get base product code (without size suffix)
            $baseCode = $first->product_code;
            $lastDashPos = strrpos($baseCode, '-');
            if ($lastDashPos !== false) {
                $suffix = substr($baseCode, $lastDashPos + 1);
                if ($suffix === $first->size || in_array($suffix, ['S', 'M', 'L', 'XL', 'XXL']) || is_numeric($suffix)) {
                    $baseCode = substr($baseCode, 0, $lastDashPos);
                }
            }
            
            return [
                'id' => $first->id, // Use first ID as representative (for backward compatibility)
                'name' => $first->name,
                'product_code' => $baseCode, // Use base product code without size suffix
                'category' => $first->category,
                'gender' => $first->gender,
                'sizes' => $allSizes, // All possible sizes for this category (for backward compatibility)
                'sizes_with_ids' => $sizesWithIds, // Sizes with product IDs and stock info
                'available_sizes' => $availableSizes, // Sizes that are actually in stock
                'stock_quantity' => $totalStock,
                'image_path' => $first->image_path ? asset('storage/'.$first->image_path) : '',
            ];
        })->values();
        
        // Prepare uniforms with image URLs as array for JSON
        $uniformsForJson = $uniformsGrouped->map(function($u) {
            return [
                'id' => $u['id'],
                'name' => $u['name'],
                'product_code' => $u['product_code'],
                'category' => $u['category'],
                'gender' => $u['gender'],
                'sizes' => $u['sizes'], // All possible sizes (for backward compatibility)
                'sizes_with_ids' => $u['sizes_with_ids'] ?? [], // Sizes with product IDs and stock info
                'available_sizes' => $u['available_sizes'] ?? [], // Sizes in stock
                'stock_quantity' => $u['stock_quantity'],
                'image_path' => $u['image_path'],
            ];
        });

        return view('applications.create', [
            'employees' => $employees,
            'deliveryLocations' => $deliveryLocations,
            'departments' => $departments,
            'jobTypes' => $jobTypes,
            'uniforms' => $uniforms,
            'uniformsJson' => $uniformsForJson,
            'customer' => $customer,
        ]);
    }

    public function getUniformsByDeptJob(Request $request)
    {
        $deptId = $request->input('department_id');
        $jobTypeId = $request->input('job_type_id');
        $gender = $request->input('gender');
        $employeeId = $request->input('employee_id');

        $customer = $request->user()->customerUser;
        if (!$customer) {
            return response()->json([]);
        }

        $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);

        // Prefer plan-linked products when employee is selected (SPEC)
        if ($employeeId) {
            $employee = Employee::where('id', $employeeId)
                ->where('medical_institution_id', $customer->medical_institution_id)
                ->first();

            if (!$employee) {
                return response()->json([]);
            }

            if ($customer->role === 'department_admin' && $customer->department_id
                && (int) $employee->primary_department_id !== (int) $customer->department_id) {
                return response()->json([]);
            }

            $products = $eligibility->eligibleProducts($employee, now(), $gender ?: null);

            return response()->json($products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'product_code' => $product->product_code,
                    'name' => $product->name,
                    'category' => $product->category,
                    'size' => $product->size,
                    'gender' => $product->gender,
                    'stock_quantity' => $product->stock_quantity,
                    'image_path' => $product->image_path ? asset('storage/'.$product->image_path) : null,
                ];
            })->values());
        }

        // Departmental / fallback: union of products from institution's confirmed plans
        $planProductIds = \App\Models\CustomerContractPlan::query()
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'confirmed')
            ->with('contractPlan.uniformProducts')
            ->get()
            ->flatMap(fn ($ccp) => $ccp->isActiveOn(now())
                ? ($ccp->contractPlan?->uniformProducts?->pluck('id') ?? collect())
                : collect())
            ->unique()
            ->values()
            ->all();

        $query = UniformProduct::query();
        if (! empty($planProductIds)) {
            $query->whereIn('id', $planProductIds);
        } else {
            // No confirmed plans → nothing applyable
            return response()->json([]);
        }

        if ($gender) {
            $query->where(function ($q) use ($gender) {
                $q->where('gender', $gender)
                  ->orWhere('gender', 'unisex');
            });
        }

        $uniforms = $query->orderBy('product_code')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'product_code' => $product->product_code,
                'name' => $product->name,
                'category' => $product->category,
                'size' => $product->size,
                'gender' => $product->gender,
                'stock_quantity' => $product->stock_quantity,
                'image_path' => $product->image_path ? asset('storage/'.$product->image_path) : null,
            ];
        });

        return response()->json($uniforms);
    }

    public function getEmployeesByDepartment(Request $request)
    {
        $departmentId = $request->input('department_id');
        $customer = $request->user()->customerUser;

        if (!$customer || !$departmentId) {
            return response()->json([]);
        }

        // 権限チェック（文字列／整数の型差を吸収）
        if ($customer->role === 'department_admin' && (int) $departmentId !== (int) $customer->department_id) {
            return response()->json([]);
        }

        $employees = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->where('primary_department_id', $departmentId)
            ->where('status', 'employed')
            ->with('positions.jobType')
            ->orderBy('staff_no')
            ->get();

        return response()->json($employees);
    }

    public function searchEmployees(Request $request)
    {
        $customer = $request->user()->customerUser;
        if (!$customer) {
            return response()->json([]);
        }

        $search = $request->input('q', '');
        $departmentId = $request->input('department_id');

        $query = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'employed')
            ->with([
                'primaryDepartment',
                'positions.jobType',
                'contractPlanAssignments.customerContractPlan.contractPlan',
            ]);

        // 権限チェック
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->where('primary_department_id', $customer->department_id);
        } elseif ($departmentId) {
            $query->where('primary_department_id', $departmentId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('staff_no', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('staff_no')->limit(20)->get();
        $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);

        return response()->json($employees->map(function ($emp) use ($eligibility) {
            $today = now()->toDateString();
            $activeAssignments = $eligibility->activeAssignments($emp, $today);
            $activePlans = $activeAssignments->map(function ($a) {
                $plan = $a->customerContractPlan?->contractPlan;

                return $plan ? [
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'rentable_points' => $plan->rentable_points,
                    'active' => true,
                ] : null;
            })->filter()->values();

            // Confirmed assignments that are outside date window (e.g. CCP not started yet)
            $pendingPlans = $emp->contractPlanAssignments
                ->filter(function ($a) use ($today) {
                    $ccp = $a->customerContractPlan;
                    if (! $ccp || $ccp->status !== 'confirmed') {
                        return false;
                    }
                    if (! $a->isActiveOn($today)) {
                        return false;
                    }

                    // Assignment ok but CCP date window not active yet/anymore
                    return ! $ccp->isActiveOn($today);
                })
                ->map(function ($a) {
                    $ccp = $a->customerContractPlan;
                    $plan = $ccp?->contractPlan;
                    if (! $plan) {
                        return null;
                    }
                    $reason = null;
                    if ($ccp->start_date && $ccp->start_date->toDateString() > now()->toDateString()) {
                        $reason = '開始前: '.$ccp->start_date->format('Y-m-d');
                    } elseif ($ccp->end_date && $ccp->end_date->toDateString() < now()->toDateString()) {
                        $reason = '終了済: '.$ccp->end_date->format('Y-m-d');
                    }

                    return [
                        'code' => $plan->code,
                        'name' => $plan->name,
                        'rentable_points' => $plan->rentable_points,
                        'active' => false,
                        'reason' => $reason,
                        'start_date' => optional($ccp->start_date)->format('Y-m-d'),
                        'end_date' => optional($ccp->end_date)->format('Y-m-d'),
                    ];
                })
                ->filter()
                ->unique('code')
                ->values();

            $planLabel = $activePlans->pluck('name')->implode(' / ');
            if ($planLabel === '' && $pendingPlans->isNotEmpty()) {
                $planLabel = $pendingPlans->map(function ($p) {
                    return $p['name'].($p['reason'] ? '（'.$p['reason'].'）' : '');
                })->implode(' / ');
            }

            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'staff_no' => $emp->staff_no,
                'gender' => $emp->gender,
                'top_size' => $emp->top_size,
                'bottom_size' => $emp->bottom_size,
                'shoe_size' => $emp->shoe_size,
                'inseam_cm' => $emp->inseam_cm,
                'body_type' => $emp->body_type,
                'department_id' => $emp->primary_department_id,
                'department_name' => $emp->primaryDepartment?->name,
                'job_type_id' => $emp->positions->first()?->job_type_id,
                'job_type_name' => $emp->positions->first()?->jobType?->name,
                'plans' => $activePlans,
                'pending_plans' => $pendingPlans,
                'plan_names' => $planLabel,
                'has_active_plan' => $activePlans->isNotEmpty(),
                'rentable_limit' => $eligibility->rentableLimit($emp),
            ];
        }));
    }

    public function getEmployeeLoans(Request $request, Employee $employee)
    {
        $customer = $request->user()->customerUser;
        if (!$customer) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ((int) $employee->medical_institution_id !== (int) $customer->medical_institution_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($customer->role === 'department_admin' && $customer->department_id
            && (int) $employee->primary_department_id !== (int) $customer->department_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $loans = EmployeeLoan::with('uniformProduct')
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($loan) {
                return [
                    'loan_id' => $loan->id,
                    'uniform_product_id' => $loan->uniform_product_id,
                    'quantity' => $loan->quantity,
                    'product_code' => $loan->uniformProduct?->product_code,
                    'product_name' => $loan->uniformProduct?->name,
                    'size' => $loan->uniformProduct?->size,
                    'category' => $loan->uniformProduct?->category,
                ];
            });

        return response()->json($loans);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var CustomerUser|null $customer */
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403);
        }

        $data = $request->validate([
            'application_mode' => ['required', 'in:individual,departmental'],
            'employee_id' => ['required_if:application_mode,individual', 'nullable', 'exists:employees,id'],
            'transfer_to_employee_id' => ['nullable', 'required_if:type,transfer', 'exists:employees,id', 'different:employee_id'],
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:new_loan,resize,return,exchange,transfer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'delivery_location_id' => ['required', 'exists:delivery_locations,id'],
            'items' => ['nullable', 'array'],
            'items.*.uniform_product_id' => ['required_with:items', 'exists:uniform_products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'return_items' => ['nullable', 'array'],
            'return_items.*.uniform_product_id' => ['required_with:return_items', 'exists:uniform_products,id'],
            'return_items.*.quantity' => ['required_with:return_items', 'integer', 'min:1'],
            'special_approval_reason' => ['nullable', 'string', 'max:1000'],
            'memo1' => ['nullable', 'string', 'max:500'],
            'memo2' => ['nullable', 'string', 'max:500'],
        ], [
            'employee_id.required_if' => '職員を選択してください。',
            'transfer_to_employee_id.required_if' => '移管先の職員を選択してください。',
            'transfer_to_employee_id.different' => '移管先は申請元と別の職員を選択してください。',
            'department_id.required' => '部署を選択してください。',
            'end_date.required' => '貸出終了日を入力してください。',
        ]);

        $data['items'] = $data['items'] ?? [];
        $data['return_items'] = $data['return_items'] ?? [];

        // Normalize UI payloads: loan checkboxes post as return_items; transfer needs items[]
        if ($data['type'] === 'transfer' && empty($data['items']) && ! empty($data['return_items'])) {
            $data['items'] = $data['return_items'];
            $data['return_items'] = [];
        }

        // 返却のみ: return_items が空なら items を返却扱い
        if ($data['type'] === 'return' && empty($data['return_items']) && ! empty($data['items'])) {
            $data['return_items'] = $data['items'];
            $data['items'] = [];
        }

        // 種別ごとの明細必須チェック
        if (in_array($data['type'], ['new_loan', 'transfer'], true) && empty($data['items'])) {
            return redirect()->back()->withInput()->with('error',
                $data['type'] === 'transfer'
                    ? '移管する貸与品を選択してください。'
                    : '少なくとも1つのユニフォームを選択してください。'
            );
        }
        if ($data['type'] === 'return' && empty($data['return_items'])) {
            return redirect()->back()->withInput()->with('error', '返却する貸与品を選択してください。');
        }
        if (in_array($data['type'], ['exchange', 'resize'], true)
            && empty($data['items']) && empty($data['return_items'])) {
            return redirect()->back()->withInput()->with('error', '旧品または新品のユニフォームを選択してください。');
        }

        if ($data['application_mode'] === 'departmental' && $data['type'] === 'transfer') {
            return redirect()->back()->withInput()->with('error', '移管申請は個別申請のみ対応しています。');
        }

        // 部署・配送先は自医療機関に属するもののみ
        $department = Department::where('id', $data['department_id'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->first();
        if (!$department) {
            return redirect()->back()->withInput()->with('error', '選択された部署は自医療機関のものではありません。');
        }

        $deliveryLocation = DeliveryLocation::where('id', $data['delivery_location_id'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->first();
        if (!$deliveryLocation) {
            return redirect()->back()->withInput()->with('error', '選択された配送先は自医療機関のものではありません。');
        }

        if ($data['application_mode'] === 'individual') {
            return $this->storeIndividual($request, $data, $customer);
        } else {
            return $this->storeDepartmental($request, $data, $customer);
        }
    }

    private function itemActionForType(string $type): string
    {
        return match ($type) {
            'return' => 'return',
            'exchange', 'resize' => 'loan', // 選定商品は新品側。旧品は別明細で exchange_out を想定
            'transfer' => 'transfer_change',
            default => 'loan',
        };
    }

    private function storeIndividual(Request $request, array $data, CustomerUser $customer): RedirectResponse
    {
        $employee = Employee::findOrFail($data['employee_id']);

        // 医療機関・部署の所有権チェック
        if ((int) $employee->medical_institution_id !== (int) $customer->medical_institution_id) {
            return redirect()->back()->withInput()->with('error', '自医療機関の職員のみ申請できます。');
        }

        if ($customer->role === 'department_admin' && (int) $employee->primary_department_id !== (int) $customer->department_id) {
            return redirect()->back()->with('error', '自部署の職員のみ申請できます。');
        }

        $transferToId = null;
        if ($data['type'] === 'transfer') {
            $transferTo = Employee::findOrFail($data['transfer_to_employee_id']);
            if ((int) $transferTo->medical_institution_id !== (int) $customer->medical_institution_id) {
                return redirect()->back()->withInput()->with('error', '移管先は自医療機関の職員を選択してください。');
            }
            if ($customer->role === 'department_admin'
                && (int) $transferTo->primary_department_id !== (int) $customer->department_id) {
                return redirect()->back()->withInput()->with('error', '移管先は自部署の職員を選択してください。');
            }
            $transferToId = $transferTo->id;
        }

        $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);
        if (! $eligibility->hasActivePlan($employee)) {
            return redirect()->back()->withInput()->with('error', 'この職員に有効な契約プランが割り当てられていません。カマタニへプラン割当を依頼してください。');
        }

        $allowedProductIds = $eligibility->productIdsAllowed($employee);
        foreach ($data['items'] as $item) {
            if (! in_array((int) $item['uniform_product_id'], array_map('intval', $allowedProductIds), true)) {
                return redirect()->back()->withInput()->with('error', '選択したユニフォームは職員の契約プラン対象外です。');
            }
        }

        // Return/transfer lines are existing loans — still must belong to this employee
        if (in_array($data['type'], ['return', 'transfer', 'exchange', 'resize'], true)) {
            $loanProductIds = EmployeeLoan::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'in_use')
                ->pluck('uniform_product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $linesToCheck = $data['type'] === 'transfer' ? $data['items'] : $data['return_items'];
            foreach ($linesToCheck as $item) {
                if (! in_array((int) $item['uniform_product_id'], $loanProductIds, true)) {
                    return redirect()->back()->withInput()->with('error', '選択した貸与品はこの職員の貸与中データと一致しません。');
                }
            }
        }

        // 上限チェック（プラン貸与ポイント／旧契約フォールバック）
        $totalQuantity = array_sum(array_column($data['items'], 'quantity'));
        $currentLoans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->sum('quantity');

        $exceedsLimit = false;
        $rentableLimit = $eligibility->rentableLimit($employee);
        if ($rentableLimit !== null && $rentableLimit > 0 && in_array($data['type'], ['new_loan', 'exchange', 'resize'], true)) {
            if (($currentLoans + $totalQuantity) > $rentableLimit) {
                $exceedsLimit = true;
            }
        }

        // 在庫チェック（新品側のみ）
        $insufficientStock = false;
        if (!empty($data['items']) && !in_array($data['type'], ['return', 'transfer'], true)) {
            foreach ($data['items'] as $item) {
                $product = UniformProduct::find($item['uniform_product_id']);
                if ($product && $product->stock_quantity < $item['quantity']) {
                    $insufficientStock = true;
                    break;
                }
            }
        }

        $status = 'pending';
        if (($exceedsLimit || $insufficientStock)) {
            if (empty($data['special_approval_reason']) || trim($data['special_approval_reason']) === '') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '契約上限または在庫を超えています。特別承認理由を入力してください。');
            }
            $status = 'waiting_special_approval';
        }

        DB::transaction(function () use ($data, $request, $customer, $employee, $status, $transferToId) {
            $application = Application::create([
                'medical_institution_id' => $customer->medical_institution_id,
                'department_id' => $employee->primary_department_id,
                'employee_id' => $employee->id,
                'transfer_to_employee_id' => $transferToId,
                'type' => $data['type'],
                'status' => $status,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'delivery_location_id' => $data['delivery_location_id'],
                'requested_by_user_id' => $request->user()->id,
                'special_approval_reason' => $data['special_approval_reason'] ?? null,
                'memo1' => $data['memo1'] ?? null,
                'memo2' => $data['memo2'] ?? null,
            ]);

            // 旧品（返却／交換アウト）
            foreach ($data['return_items'] as $item) {
                ApplicationItem::create([
                    'application_id' => $application->id,
                    'uniform_product_id' => $item['uniform_product_id'],
                    'quantity' => $item['quantity'],
                    'action' => in_array($data['type'], ['exchange', 'resize'], true) ? 'exchange_out' : 'return',
                ]);
            }

            // 新品／移管対象
            $newAction = $this->itemActionForType($data['type']);
            foreach ($data['items'] as $item) {
                ApplicationItem::create([
                    'application_id' => $application->id,
                    'uniform_product_id' => $item['uniform_product_id'],
                    'quantity' => $item['quantity'],
                    'action' => $newAction,
                ]);
            }

            \App\Services\NotificationService::notifyApplicationCreated($application);

            if ($status === 'waiting_special_approval') {
                \App\Services\NotificationService::notifySpecialApprovalRequested($application);
            }
        });

        $message = $status === 'waiting_special_approval'
            ? '申請を登録しました。特別承認待ちの状態です。'
            : '申請を登録しました。';

        return redirect()
            ->route('applications.create')
            ->with('status', $message)
            ->with('success', true);
    }

    private function storeDepartmental(Request $request, array $data, CustomerUser $customer): RedirectResponse
    {
        $departmentId = (int) $data['department_id'];

        // 権限チェック（文字列／整数の型差を吸収）
        if ($customer->role === 'department_admin' && $departmentId !== (int) $customer->department_id) {
            return redirect()->back()->with('error', '自部署のみ申請できます。');
        }

        // Get all active employees in the department
        $employees = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->where('primary_department_id', $departmentId)
            ->where('status', 'employed')
            ->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', '該当部署に在籍している職員がありません。');
        }

        $createdCount = 0;
        $errors = [];
        $itemAction = $this->itemActionForType($data['type']);

        DB::transaction(function () use ($data, $request, $customer, $employees, $itemAction, &$createdCount, &$errors) {
            $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);

            foreach ($employees as $employee) {
                if (! $eligibility->hasActivePlan($employee)) {
                    $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 有効な契約プランが未割当のためスキップしました。";
                    continue;
                }

                $allowedProductIds = array_map('intval', $eligibility->productIdsAllowed($employee));
                foreach ($data['items'] as $item) {
                    if (! in_array((int) $item['uniform_product_id'], $allowedProductIds, true)) {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 選択商品が契約プラン対象外のためスキップしました。";
                        continue 2;
                    }
                }

                if (in_array($data['type'], ['return', 'exchange', 'resize'], true) && ! empty($data['return_items'])) {
                    $loanProductIds = EmployeeLoan::query()
                        ->where('employee_id', $employee->id)
                        ->where('status', 'in_use')
                        ->pluck('uniform_product_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    $hasMatchingReturn = false;
                    foreach ($data['return_items'] as $item) {
                        if (in_array((int) $item['uniform_product_id'], $loanProductIds, true)) {
                            $hasMatchingReturn = true;
                            break;
                        }
                    }

                    if ($data['type'] === 'return' && ! $hasMatchingReturn) {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 返却対象の貸与品がないためスキップしました。";
                        continue;
                    }
                }

                $totalQuantity = array_sum(array_column($data['items'], 'quantity'));
                $currentLoans = EmployeeLoan::where('employee_id', $employee->id)
                    ->where('status', 'in_use')
                    ->sum('quantity');

                $exceedsLimit = false;
                $rentableLimit = $eligibility->rentableLimit($employee);
                if ($rentableLimit !== null && $rentableLimit > 0 && in_array($data['type'], ['new_loan', 'exchange', 'resize'], true)) {
                    if (($currentLoans + $totalQuantity) > $rentableLimit) {
                        $exceedsLimit = true;
                    }
                }

                $insufficientStock = false;
                if (! empty($data['items']) && ! in_array($data['type'], ['return', 'transfer'], true)) {
                    foreach ($data['items'] as $item) {
                        $product = UniformProduct::find($item['uniform_product_id']);
                        if ($product && $product->stock_quantity < ($item['quantity'] * $employees->count())) {
                            $insufficientStock = true;
                            break;
                        }
                    }
                }

                $status = 'pending';
                if ($exceedsLimit || $insufficientStock) {
                    if (empty($data['special_approval_reason']) || trim($data['special_approval_reason']) === '') {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 契約上限または在庫を超えています。特別承認理由を入力してください。";
                        continue;
                    }
                    $status = 'waiting_special_approval';
                }

                $application = Application::create([
                    'medical_institution_id' => $customer->medical_institution_id,
                    'department_id' => $employee->primary_department_id,
                    'employee_id' => $employee->id,
                    'type' => $data['type'],
                    'status' => $status,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'delivery_location_id' => $data['delivery_location_id'],
                    'requested_by_user_id' => $request->user()->id,
                    'special_approval_reason' => $data['special_approval_reason'] ?? null,
                    'memo1' => $data['memo1'] ?? null,
                    'memo2' => $data['memo2'] ?? null,
                ]);

                $itemCount = 0;

                foreach ($data['return_items'] as $item) {
                    if (! in_array($data['type'], ['return', 'exchange', 'resize'], true)) {
                        break;
                    }

                    $holds = EmployeeLoan::query()
                        ->where('employee_id', $employee->id)
                        ->where('uniform_product_id', $item['uniform_product_id'])
                        ->where('status', 'in_use')
                        ->exists();
                    if (! $holds) {
                        continue;
                    }

                    ApplicationItem::create([
                        'application_id' => $application->id,
                        'uniform_product_id' => $item['uniform_product_id'],
                        'quantity' => $item['quantity'],
                        'action' => in_array($data['type'], ['exchange', 'resize'], true) ? 'exchange_out' : 'return',
                    ]);
                    $itemCount++;
                }

                foreach ($data['items'] as $item) {
                    ApplicationItem::create([
                        'application_id' => $application->id,
                        'uniform_product_id' => $item['uniform_product_id'],
                        'quantity' => $item['quantity'],
                        'action' => $itemAction,
                    ]);
                    $itemCount++;
                }

                if ($itemCount === 0) {
                    $application->delete();
                    $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 申請明細を作成できなかったためスキップしました。";
                    continue;
                }

                \App\Services\NotificationService::notifyApplicationCreated($application);

                if ($status === 'waiting_special_approval') {
                    \App\Services\NotificationService::notifySpecialApprovalRequested($application);
                }

                $createdCount++;
            }
        });

        if (!empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode("\n", $errors));
        }

        $message = $createdCount > 0
            ? "部署一括申請を登録しました。{$createdCount}件の申請を作成しました。"
            : '申請の作成に失敗しました。';

        return redirect()
            ->route('applications.create')
            ->with('status', $message)
            ->with('success', true);
    }

    public function show(Application $application): View
    {
        $this->authorize('view', $application);

        $application->load(['employee', 'transferToEmployee', 'items.uniformProduct', 'department', 'deliveryLocation']);

        return view('applications.show', [
            'application' => $application,
        ]);
    }

    public function requestCancel(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('cancel', $application);

        if ($application->status === Application::STATUS_COMPLETED) {
            return redirect()->back()->with('error', '完了済みの申請はキャンセルできません。');
        }

        if (! $application->isCancellableByHospital()) {
            return redirect()->back()->with('error', 'この申請はキャンセル申請できません。');
        }

        $application->update(['status' => Application::STATUS_CANCEL_REQUEST]);

        \App\Services\NotificationService::notifyCancelRequested($application);

        return redirect()->back()->with('status', 'キャンセル申請を送信しました。');
    }
}
