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
use App\Models\UniformCategory;
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

        if ($customer->isMisconfiguredDepartmentAdmin()) {
            abort(403, __('pages.department_admin_missing_department'));
        }

        $query = Application::with(['employee', 'department'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->orderByDesc('created_at');

        if ($customer->isDepartmentAdmin() && $customer->department_id) {
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

        if ($customer->isMisconfiguredDepartmentAdmin()) {
            abort(403, __('pages.department_admin_missing_department'));
        }

        $query = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'employed')
            ->orderBy('staff_no');

        if ($customer->isDepartmentAdmin() && $customer->department_id) {
            $query->inDepartment((int) $customer->department_id);
        }

        $employees = $query->get();

        $deliveryLocations = DeliveryLocation::where('medical_institution_id', $customer->medical_institution_id)
            ->orderBy('name')
            ->get();

        $departments = Department::where('medical_institution_id', $customer->medical_institution_id)->get();
        $jobTypes = JobType::where('medical_institution_id', $customer->medical_institution_id)->get();
        $uniforms = UniformProduct::query()->withUsableSize()->orderBy('product_code')->get();

        $categories = UniformCategory::activeOrdered();
        $letterCodes = UniformCategory::codesWithScheme(UniformCategory::SIZE_LETTER);
        $shoeCodes = UniformCategory::codesWithScheme(UniformCategory::SIZE_SHOE);

        $topBottomSizes = $letterCodes
            ? UniformProduct::whereIn('category', $letterCodes)
                ->select('size')
                ->distinct()
                ->pluck('size')
                ->filter()
                ->values()
                ->all()
            : [];
        $letterOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
        usort($topBottomSizes, fn ($a, $b) => ($letterOrder[$a] ?? 99) <=> ($letterOrder[$b] ?? 99));

        $shoeSizes = $shoeCodes
            ? UniformProduct::whereIn('category', $shoeCodes)
                ->select('size')
                ->distinct()
                ->pluck('size')
                ->filter()
                ->values()
                ->all()
            : [];
        usort($shoeSizes, fn ($a, $b) => (float) $a <=> (float) $b);
        
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
            $allSizes = UniformCategory::sizeSchemeFor($first->category) === UniformCategory::SIZE_SHOE ? $shoeSizes : $topBottomSizes;
            
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
                'category_label' => UniformCategory::labelFor($first->category),
                'size_scheme' => UniformCategory::sizeSchemeFor($first->category),
                'employee_size_field' => UniformCategory::findByCode($first->category)?->employee_size_field,
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
                'category_label' => $u['category_label'] ?? UniformCategory::labelFor($u['category']),
                'size_scheme' => $u['size_scheme'] ?? UniformCategory::sizeSchemeFor($u['category']),
                'employee_size_field' => $u['employee_size_field'] ?? null,
                'gender' => $u['gender'],
                'sizes' => $u['sizes'], // All possible sizes (for backward compatibility)
                'sizes_with_ids' => $u['sizes_with_ids'] ?? [], // Sizes with product IDs and stock info
                'available_sizes' => $u['available_sizes'] ?? [], // Sizes in stock
                'stock_quantity' => $u['stock_quantity'],
                'image_path' => $u['image_path'],
            ];
        });

        $recommendationHints = \App\Models\UniformRecommendation::with('uniformProduct')
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->orderByDesc('is_required')
            ->orderBy('id')
            ->limit(50)
            ->get();

        return view('applications.create', [
            'employees' => $employees,
            'deliveryLocations' => $deliveryLocations,
            'departments' => $departments,
            'jobTypes' => $jobTypes,
            'uniforms' => $uniforms,
            'uniformsJson' => $uniformsForJson,
            'categories' => $categories,
            'customer' => $customer,
            'recommendationHints' => $recommendationHints,
        ]);
    }

    public function getUniformsByPlan(Request $request)
    {
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
                && ! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
                return response()->json([]);
            }

            $products = $eligibility->eligibleProducts($employee, now(), $gender ?: null);

            return response()->json($products->map(function ($product) use ($eligibility, $employee) {
                $limitCategory = $eligibility->limitCategoryForProduct($employee, (int) $product->id);

                return [
                    'id' => $product->id,
                    'product_code' => $product->product_code,
                    'name' => $product->name,
                    'category' => $product->category,
                    'limit_category' => $limitCategory,
                    'slot_category' => $product->pivot?->slot_category,
                    'category_label' => UniformCategory::labelFor($product->category),
                    'size_scheme' => UniformCategory::sizeSchemeFor($product->category),
                    'employee_size_field' => UniformCategory::findByCode($product->category)?->employee_size_field,
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

        if (empty($planProductIds)) {
            return response()->json([]);
        }

        $query = UniformProduct::query()->whereIn('id', $planProductIds)->withUsableSize();

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
                    'category_label' => UniformCategory::labelFor($product->category),
                    'size_scheme' => UniformCategory::sizeSchemeFor($product->category),
                    'employee_size_field' => UniformCategory::findByCode($product->category)?->employee_size_field,
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

        if (! $customer) {
            return response()->json([]);
        }

        $query = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'employed')
            ->with('positions.jobType')
            ->orderBy('staff_no');

        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->inDepartment((int) $customer->department_id);
        } elseif ($departmentId) {
            $query->inDepartment((int) $departmentId);
        }

        return response()->json($query->get());
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
                'positions.department',
                'positions.jobType',
                'contractPlanAssignments.customerContractPlan.contractPlan',
            ]);

        // 権限チェック（主所属または兼務）
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->inDepartment((int) $customer->department_id);
        } elseif ($departmentId) {
            $query->inDepartment((int) $departmentId);
        }

        if ($search) {
            $exact = (clone $query)->where('staff_no', $search)->limit(20)->get();
            if ($exact->isNotEmpty()) {
                $employees = $exact;
            } else {
                $prefix = (clone $query)->where('staff_no', 'like', $search.'%')->orderBy('staff_no')->limit(20)->get();
                if ($prefix->isNotEmpty()) {
                    $employees = $prefix;
                } else {
                    $employees = $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('staff_no', 'like', "%{$search}%");
                    })->orderBy('staff_no')->limit(20)->get();
                }
            }
        } else {
            $employees = $query->orderBy('staff_no')->limit(20)->get();
        }

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
                'affiliation_departments' => $emp->affiliationDepartmentNames(),
                'job_type_id' => $emp->positions->first()?->job_type_id,
                'job_type_name' => $emp->positions->first()?->jobType?->name,
                'plans' => $activePlans,
                'pending_plans' => $pendingPlans,
                'plan_names' => $planLabel,
                'has_active_plan' => $activePlans->isNotEmpty(),
                'rentable_limit' => $eligibility->rentableLimit($emp),
                'rentable_limits_by_category' => $eligibility->categoryCaps($emp),
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
            && ! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);

        $loans = EmployeeLoan::with('uniformProduct')
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($loan) use ($eligibility, $employee) {
                return [
                    'loan_id' => $loan->id,
                    'uniform_product_id' => $loan->uniform_product_id,
                    'quantity' => $loan->quantity,
                    'product_code' => $loan->uniformProduct?->product_code,
                    'product_name' => $loan->uniformProduct?->name,
                    'size' => $loan->uniformProduct?->size,
                    'category' => $loan->uniformProduct?->category,
                    'limit_category' => $eligibility->limitCategoryForProduct($employee, (int) $loan->uniform_product_id),
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
            'application_mode' => ['required', 'in:individual'],
            'employee_id' => ['required', 'exists:employees,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'type' => ['required', 'in:new_loan,resize,return,exchange,transfer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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
            'employee_id.required' => __('pages.select_employee_required'),
            'application_mode.in' => __('pages.individual_applications_only'),
        ]);

        $data['application_mode'] = 'individual';

        // Loan-period types still need an end date (異動 = same-employee partial change, like exchange)
        if (in_array($data['type'], ['new_loan', 'exchange', 'resize', 'transfer'], true) && empty($data['end_date'])) {
            return redirect()->back()->withInput()->withErrors([
                'end_date' => __('pages.loan_end_date_required'),
            ]);
        }

        // Individual apps: prefer applying dept for affiliated staff, else primary
        if (empty($data['department_id']) && ! empty($data['employee_id'])) {
            $emp = Employee::find($data['employee_id']);
            if ($customer->isDepartmentAdmin() && $customer->department_id
                && $emp && $emp->isAffiliatedWithDepartment((int) $customer->department_id)) {
                $data['department_id'] = $customer->department_id;
            } else {
                $data['department_id'] = $emp?->primary_department_id;
            }
        }

        // Department admins may only tag applications to their own department
        if ($customer->isDepartmentAdmin()) {
            if (! $customer->department_id) {
                abort(403, __('pages.department_admin_missing_department'));
            }
            $data['department_id'] = (int) $customer->department_id;
        }

        if (empty($data['department_id'])) {
            return redirect()->back()->withInput()->withErrors([
                'department_id' => __('pages.select_department_required'),
            ]);
        }

        // Ensure department belongs to this institution
        $deptOk = \App\Models\Department::query()
            ->where('id', $data['department_id'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->exists();
        if (! $deptOk) {
            return redirect()->back()->withInput()->withErrors([
                'department_id' => __('pages.flash_0add709231'),
            ]);
        }

        if (empty($data['end_date'])) {
            $data['end_date'] = $data['start_date'];
        }

        $data['items'] = $data['items'] ?? [];
        $data['return_items'] = $data['return_items'] ?? [];

        // 返却のみ: return_items が空なら items を返却扱い
        if ($data['type'] === 'return' && empty($data['return_items']) && ! empty($data['items'])) {
            $data['return_items'] = $data['items'];
            $data['items'] = [];
        }

        // 種別ごとの明細必須チェック
        // 異動（部分変更）= exchange と同形（旧品 return_items + 新品 items）
        if ($data['type'] === 'new_loan' && empty($data['items'])) {
            return redirect()->back()->withInput()->with('error', __('pages.select_at_least_one_uniform'));
        }
        if ($data['type'] === 'return' && empty($data['return_items'])) {
            return redirect()->back()->withInput()->with('error', __('pages.flash_e248dd7f96'));
        }
        if (in_array($data['type'], ['exchange', 'resize', 'transfer'], true)
            && empty($data['items']) && empty($data['return_items'])) {
            return redirect()->back()->withInput()->with('error', __('pages.flash_8fc8780f3f'));
        }

        // Resize / exchange / partial change: new loan qty cannot exceed selected change qty
        if (in_array($data['type'], ['exchange', 'resize', 'transfer'], true)) {
            $returnQty = (int) array_sum(array_map(
                fn ($row) => (int) ($row['quantity'] ?? 0),
                $data['return_items'] ?? []
            ));
            $newQty = (int) array_sum(array_map(
                fn ($row) => (int) ($row['quantity'] ?? 0),
                $data['items'] ?? []
            ));
            if ($returnQty <= 0) {
                return redirect()->back()->withInput()->with('error', __('pages.select_change_items'));
            }
            if ($newQty > $returnQty) {
                return redirect()->back()->withInput()->with('error', __('pages.new_qty_exceeds_change_qty'));
            }
            if (in_array($data['type'], ['exchange', 'resize'], true) && $newQty <= 0) {
                return redirect()->back()->withInput()->with('error', __('pages.select_at_least_one_uniform'));
            }
        }

        if ($data['application_mode'] === 'departmental' && $data['type'] === 'transfer') {
            return redirect()->back()->withInput()->with('error', __('pages.flash_1f76c65a74'));
        }

        // 部署・配送先は自医療機関に属するもののみ
        $department = Department::where('id', $data['department_id'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->first();
        if (!$department) {
            return redirect()->back()->withInput()->with('error', __('pages.flash_0add709231'));
        }

        $deliveryLocation = DeliveryLocation::where('id', $data['delivery_location_id'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->first();
        if (!$deliveryLocation) {
            return redirect()->back()->withInput()->with('error', __('pages.flash_8e7dad2ab5'));
        }

        return $this->storeIndividual($request, $data, $customer);
    }

    private function itemActionForType(string $type): string
    {
        return match ($type) {
            'return' => 'return',
            // 選定商品は新品側。旧品は別明細で exchange_out（異動も同形）
            'exchange', 'resize', 'transfer' => 'loan',
            default => 'loan',
        };
    }

    private function storeIndividual(Request $request, array $data, CustomerUser $customer): RedirectResponse
    {
        $employee = Employee::findOrFail($data['employee_id']);

        // 医療機関・部署の所有権チェック
        if ((int) $employee->medical_institution_id !== (int) $customer->medical_institution_id) {
            return redirect()->back()->withInput()->with('error', __('pages.flash_a344f2a6e6'));
        }

        if ($customer->isDepartmentAdmin()) {
            if (! $customer->department_id) {
                abort(403, __('pages.department_admin_missing_department'));
            }
            if (! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
                return redirect()->back()->with('error', __('pages.flash_c744c00190'));
            }
        }

        $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);
        $loanInventory = app(\App\Services\LoanInventoryService::class);
        $isReturnOnly = $data['type'] === 'return';

        // Pure returns must work even after plan detach / before reassignment
        if (! $isReturnOnly && ! $eligibility->hasActivePlan($employee)) {
            return redirect()->back()->withInput()->with('error', __('pages.flash_3b42393b1f'));
        }

        if (! $isReturnOnly) {
            $allowedProductIds = $eligibility->productIdsAllowed($employee);
            foreach ($data['items'] as $item) {
                if (! in_array((int) $item['uniform_product_id'], array_map('intval', $allowedProductIds), true)) {
                    return redirect()->back()->withInput()->with('error', __('pages.flash_7d5b9dface'));
                }
            }
        }

        // 旧品（返却／異動変更）は当該職員の貸与中データと数量一致必須
        $returnItemsForLimit = [];
        if (in_array($data['type'], ['return', 'transfer', 'exchange', 'resize'], true)) {
            $returnError = $loanInventory->returnItemsValidationError(
                (int) $employee->id,
                $data['return_items'] ?? []
            );
            if ($returnError) {
                return redirect()->back()->withInput()->with('error', __($returnError));
            }
            $returnItemsForLimit = $data['return_items'] ?? [];
        } elseif ($data['type'] === 'new_loan' && ! empty($data['return_items'])) {
            // new_loan must not credit forged returns against the rentable cap
            return redirect()->back()->withInput()->with('error', __('pages.new_loan_cannot_include_returns'));
        }

        // 上限チェック（カテゴリ別 or 合計）— 異動の新品側も含む
        if (in_array($data['type'], ['new_loan', 'exchange', 'resize', 'transfer'], true)) {
            $violation = $eligibility->loanLimitViolation(
                $employee,
                $data['items'] ?? [],
                $returnItemsForLimit,
                null,
                true
            );
            if ($violation) {
                $messageKey = ($violation['mode'] ?? '') === 'category'
                    ? 'pages.rentable_category_limit_hard_block'
                    : 'pages.rentable_limit_hard_block';

                return redirect()->back()
                    ->withInput()
                    ->with('error', __($messageKey, [
                        'category' => isset($violation['category'])
                            ? UniformCategory::labelFor($violation['category'])
                            : '',
                        'limit' => $violation['limit'],
                        'projected' => $violation['projected'],
                    ]));
            }
        }

        // 在庫チェック（新品側のみ）— aggregate by product; special approval for stock only
        $insufficientStock = false;
        if (! empty($data['items']) && $data['type'] !== 'return') {
            $requestedByProduct = [];
            foreach ($data['items'] as $item) {
                $pid = (int) $item['uniform_product_id'];
                $requestedByProduct[$pid] = ($requestedByProduct[$pid] ?? 0) + (int) $item['quantity'];
            }
            foreach ($requestedByProduct as $productId => $qty) {
                $product = UniformProduct::find($productId);
                if ($product && $product->stock_quantity < $qty) {
                    $insufficientStock = true;
                    break;
                }
            }
        }

        $status = 'pending';
        if ($insufficientStock) {
            if (empty($data['special_approval_reason']) || trim($data['special_approval_reason']) === '') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('pages.stock_special_approval_required'));
            }
            $status = 'waiting_special_approval';
        } else {
            // Clear leftover reason when stock is sufficient (prevents accept stock waiver)
            $data['special_approval_reason'] = null;
        }

        DB::transaction(function () use ($data, $request, $customer, $employee, $status) {
            $application = Application::create([
                'medical_institution_id' => $customer->medical_institution_id,
                'department_id' => $data['department_id'] ?? $employee->primary_department_id,
                'employee_id' => $employee->id,
                'transfer_to_employee_id' => null,
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

            // 旧品（返却／交換・異動アウト）— never for new_loan
            if (in_array($data['type'], ['return', 'transfer', 'exchange', 'resize'], true)) {
                foreach ($data['return_items'] ?? [] as $item) {
                    ApplicationItem::create([
                        'application_id' => $application->id,
                        'uniform_product_id' => $item['uniform_product_id'],
                        'quantity' => $item['quantity'],
                        'action' => in_array($data['type'], ['exchange', 'resize', 'transfer'], true) ? 'exchange_out' : 'return',
                    ]);
                }
            }

            // 新品
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
            return redirect()->back()->with('error', __('pages.flash_384a2940fd'));
        }

        // Get all active employees affiliated with the department (primary or concurrent)
        $employees = Employee::where('medical_institution_id', $customer->medical_institution_id)
            ->inDepartment($departmentId)
            ->where('status', 'employed')
            ->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', __('pages.flash_bc7261c9b7'));
        }

        $createdCount = 0;
        $errors = [];
        $itemAction = $this->itemActionForType($data['type']);

        DB::transaction(function () use ($data, $request, $customer, $employees, $itemAction, $departmentId, &$createdCount, &$errors) {
            $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);
            $loanInventory = app(\App\Services\LoanInventoryService::class);
            $isReturnOnly = $data['type'] === 'return';

            foreach ($employees as $employee) {
                if (! $isReturnOnly && ! $eligibility->hasActivePlan($employee)) {
                    $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 有効な契約プランが未割当のためスキップしました。";
                    continue;
                }

                if (! $isReturnOnly) {
                    $allowedProductIds = array_map('intval', $eligibility->productIdsAllowed($employee));
                    foreach ($data['items'] as $item) {
                        if (! in_array((int) $item['uniform_product_id'], $allowedProductIds, true)) {
                            $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 選択商品が契約プラン対象外のためスキップしました。";
                            continue 2;
                        }
                    }
                }

                if (in_array($data['type'], ['return', 'exchange', 'resize'], true) && ! empty($data['return_items'])) {
                    $held = $loanInventory->activeLoanQuantitiesByProduct((int) $employee->id);
                    $hasMatchingReturn = false;

                    foreach ($data['return_items'] as $item) {
                        $productId = (int) $item['uniform_product_id'];
                        $qty = (int) $item['quantity'];
                        if (! isset($held[$productId])) {
                            continue;
                        }
                        $hasMatchingReturn = true;
                        if ($qty > $held[$productId]) {
                            $errors[] = "職員 {$employee->name} ({$employee->staff_no}): ".__('pages.return_quantity_exceeds_loan');
                            continue 2;
                        }
                    }

                    if ($data['type'] === 'return' && ! $hasMatchingReturn) {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 返却対象の貸与品がないためスキップしました。";
                        continue;
                    }
                }

                if (in_array($data['type'], ['new_loan', 'exchange', 'resize'], true)) {
                    $violation = $eligibility->loanLimitViolation(
                        $employee,
                        $data['items'] ?? [],
                        $data['return_items'] ?? []
                    );
                    if ($violation) {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): "
                            .(($violation['mode'] ?? '') === 'category'
                                ? __('pages.rentable_category_limit_hard_block', [
                                    'category' => UniformCategory::labelFor($violation['category'] ?? ''),
                                    'limit' => $violation['limit'],
                                    'projected' => $violation['projected'],
                                ])
                                : __('pages.rentable_limit_hard_block', [
                                    'limit' => $violation['limit'],
                                    'projected' => $violation['projected'],
                                ]));
                        continue;
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
                if ($insufficientStock) {
                    if (empty($data['special_approval_reason']) || trim($data['special_approval_reason']) === '') {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 在庫不足のため特別承認理由を入力してください。";
                        continue;
                    }
                    $status = 'waiting_special_approval';
                }

                $application = Application::create([
                    'medical_institution_id' => $customer->medical_institution_id,
                    'department_id' => $departmentId,
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

        if ($createdCount > 0 && ! empty($errors)) {
            return redirect()
                ->route('applications.create')
                ->with('status', __('pages.departmental_partial_success', ['count' => $createdCount]))
                ->with('error', implode("\n", $errors))
                ->with('success', true);
        }

        if (! empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode("\n", $errors));
        }

        $message = $createdCount > 0
            ? __('pages.departmental_success', ['count' => $createdCount])
            : __('pages.departmental_failed');

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
            return redirect()->back()->with('error', __('pages.flash_b29832dab3'));
        }

        if (! $application->isCancellableByHospital()) {
            return redirect()->back()->with('error', __('pages.flash_53939f8310'));
        }

        $application->update(['status' => Application::STATUS_CANCEL_REQUEST]);

        \App\Services\NotificationService::notifyCancelRequested($application);

        return redirect()->back()->with('status', __('pages.flash_c4c5892380'));
    }
}
