<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationItem;
use App\Models\Contract;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

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

        $applications = $query->paginate(20)->withQueryString();

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

        // Get active contract for limit information
        $contract = Contract::where('medical_institution_id', $customer->medical_institution_id)
            ->where(function($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            })
            ->where(function($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->orderBy('valid_from', 'desc')
            ->first();

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
            'contract' => $contract,
        ]);
    }

    public function getUniformsByDeptJob(Request $request)
    {
        $deptId = $request->input('department_id');
        $jobTypeId = $request->input('job_type_id');
        $gender = $request->input('gender');

        $customer = $request->user()->customerUser;
        if (!$customer) {
            return response()->json([]);
        }

        $query = UniformProduct::query();

        // Note: Uniform recommendations have been removed
        // Products are global and not tied to specific departments
        // All products are available for selection regardless of department
        // However, we can filter by gender if provided

        // Filter by gender if provided
        if ($gender) {
            $query->where(function($q) use ($gender) {
                $q->where('gender', $gender)
                  ->orWhere('gender', 'unisex');
            });
        }

        // Get all products (since they're global and not department-specific)
        // Return products with all necessary fields for frontend grouping
        $uniforms = $query->orderBy('product_code')->get()->map(function($product) {
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

        // 権限チェック
        if ($customer->role === 'department_admin' && $departmentId !== $customer->department_id) {
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
            ->with(['primaryDepartment', 'positions.jobType']);

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

        return response()->json($employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'staff_no' => $emp->staff_no,
                'gender' => $emp->gender,
                'top_size' => $emp->top_size,
                'bottom_size' => $emp->bottom_size,
                'shoe_size' => $emp->shoe_size,
                'department_id' => $emp->primary_department_id,
                'department_name' => $emp->primaryDepartment?->name,
                'job_type_id' => $emp->positions->first()?->job_type_id,
                'job_type_name' => $emp->positions->first()?->jobType?->name,
            ];
        }));
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
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:new_loan,resize,return,exchange,transfer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'delivery_location_id' => ['required', 'exists:delivery_locations,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uniform_product_id' => ['required', 'exists:uniform_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'special_approval_reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'employee_id.required_if' => '職員を選択してください。',
            'department_id.required' => '部署を選択してください。',
            'end_date.required' => '貸出終了日を入力してください。',
            'items.required' => '少なくとも1つのユニフォームを選択してください。',
            'items.min' => '少なくとも1つのユニフォームを選択してください。',
        ]);

        // Get active contract
        $contract = Contract::where('medical_institution_id', $customer->medical_institution_id)
            ->where(function($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            })
            ->orderByDesc('valid_from')
            ->first();

        if ($data['application_mode'] === 'individual') {
            return $this->storeIndividual($request, $data, $customer, $contract);
        } else {
            return $this->storeDepartmental($request, $data, $customer, $contract);
        }
    }

    private function storeIndividual(Request $request, array $data, CustomerUser $customer, ?Contract $contract): RedirectResponse
    {
        // Ensure end_date column exists before transaction
        if (!Schema::hasColumn('applications', 'end_date')) {
            try {
                Schema::table('applications', function ($table) {
                    $table->date('end_date')->nullable()->after('start_date');
                });
            } catch (\Exception $e) {
                // Column might already exist or migration issue
            }
        }
        
        $employee = Employee::findOrFail($data['employee_id']);

        // 権限チェック
        if ($customer->role === 'department_admin' && $employee->primary_department_id !== $customer->department_id) {
            return redirect()->back()->with('error', '自部署の職員のみ申請できます。');
        }

        // 上限チェック
        $totalQuantity = array_sum(array_column($data['items'], 'quantity'));
        $currentLoans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->sum('quantity');

        $exceedsLimit = false;
        if ($contract && $contract->per_person_loan_limit) {
            if (($currentLoans + $totalQuantity) > $contract->per_person_loan_limit) {
                $exceedsLimit = true;
            }
        }

        // 在庫チェック
        $insufficientStock = false;
        foreach ($data['items'] as $item) {
            $product = UniformProduct::find($item['uniform_product_id']);
            if ($product && $product->stock_quantity < $item['quantity']) {
                $insufficientStock = true;
                break;
            }
        }

        $status = 'pending';
        // Only set to waiting_special_approval if conditions are met AND reason is provided
        if (($exceedsLimit || $insufficientStock)) {
            // If reason is not provided, return error
            if (empty($data['special_approval_reason']) || trim($data['special_approval_reason']) === '') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '契約上限または在庫を超えています。特別承認理由を入力してください。');
            }
            // If reason is provided, set status to waiting_special_approval
            $status = 'waiting_special_approval';
        }

        DB::transaction(function () use ($data, $request, $customer, $employee, $status) {
            $applicationData = [
                'medical_institution_id' => $customer->medical_institution_id,
                'department_id' => $employee->primary_department_id,
                'employee_id' => $employee->id,
                'type' => $data['type'],
                'status' => $status,
                'start_date' => $data['start_date'],
                'delivery_location_id' => $data['delivery_location_id'],
                'requested_by_user_id' => $request->user()->id,
                'special_approval_reason' => $data['special_approval_reason'] ?? null,
            ];
            
            // Only include end_date if column exists
            if (Schema::hasColumn('applications', 'end_date')) {
                $applicationData['end_date'] = $data['end_date'] ?? null;
            }
            
            $application = Application::create($applicationData);

            foreach ($data['items'] as $item) {
                ApplicationItem::create([
                    'application_id' => $application->id,
                    'uniform_product_id' => $item['uniform_product_id'],
                    'quantity' => $item['quantity'],
                    'action' => 'loan',
                ]);
            }

            // 通知を送信
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

    private function storeDepartmental(Request $request, array $data, CustomerUser $customer, ?Contract $contract): RedirectResponse
    {
        // Ensure end_date column exists before transaction
        if (!Schema::hasColumn('applications', 'end_date')) {
            try {
                Schema::table('applications', function ($table) {
                    $table->date('end_date')->nullable()->after('start_date');
                });
            } catch (\Exception $e) {
                // Column might already exist or migration issue
            }
        }
        
        $departmentId = $data['department_id'];

        // 権限チェック
        if ($customer->role === 'department_admin' && $departmentId !== $customer->department_id) {
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

        DB::transaction(function () use ($data, $request, $customer, $employees, $contract, &$createdCount, &$errors) {
            foreach ($employees as $employee) {
                // Check limits per employee
                $totalQuantity = array_sum(array_column($data['items'], 'quantity'));
                $currentLoans = EmployeeLoan::where('employee_id', $employee->id)
                    ->where('status', 'in_use')
                    ->sum('quantity');

                $exceedsLimit = false;
                if ($contract && $contract->per_person_loan_limit) {
                    if (($currentLoans + $totalQuantity) > $contract->per_person_loan_limit) {
                        $exceedsLimit = true;
                    }
                }

                // Check stock (aggregate check for all employees)
                $insufficientStock = false;
                foreach ($data['items'] as $item) {
                    $product = UniformProduct::find($item['uniform_product_id']);
                    if ($product && $product->stock_quantity < ($item['quantity'] * $employees->count())) {
                        $insufficientStock = true;
                        break;
                    }
                }

                $status = 'pending';
                // Only set to waiting_special_approval if conditions are met AND reason is provided
                if (($exceedsLimit || $insufficientStock)) {
                    // If reason is not provided, add error and skip this employee
                    if (empty($data['special_approval_reason']) || trim($data['special_approval_reason']) === '') {
                        $errors[] = "職員 {$employee->name} ({$employee->staff_no}): 契約上限または在庫を超えています。特別承認理由を入力してください。";
                        continue;
                    }
                    // If reason is provided, set status to waiting_special_approval
                    $status = 'waiting_special_approval';
                }

                // Ensure end_date column exists
                if (!Schema::hasColumn('applications', 'end_date')) {
                    try {
                        Schema::table('applications', function ($table) {
                            $table->date('end_date')->nullable()->after('start_date');
                        });
                    } catch (\Exception $e) {
                        // Column might already exist or migration issue
                    }
                }
                
                $applicationData = [
                    'medical_institution_id' => $customer->medical_institution_id,
                    'department_id' => $employee->primary_department_id,
                    'employee_id' => $employee->id,
                    'type' => $data['type'],
                    'status' => $status,
                    'start_date' => $data['start_date'],
                    'delivery_location_id' => $data['delivery_location_id'],
                    'requested_by_user_id' => $request->user()->id,
                    'special_approval_reason' => $data['special_approval_reason'] ?? null,
                ];
                
                // Only include end_date if column exists
                if (Schema::hasColumn('applications', 'end_date')) {
                    $applicationData['end_date'] = $data['end_date'] ?? null;
                }
                
                // Create application for this employee
                $application = Application::create($applicationData);

                foreach ($data['items'] as $item) {
                    ApplicationItem::create([
                        'application_id' => $application->id,
                        'uniform_product_id' => $item['uniform_product_id'],
                        'quantity' => $item['quantity'],
                        'action' => 'loan',
                    ]);
                }

                // 通知を送信
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
        $application->load(['employee', 'items.uniformProduct', 'department', 'deliveryLocation']);

        return view('applications.show', [
            'application' => $application,
        ]);
    }

    public function requestCancel(Request $request, Application $application): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customerUser;

        if (!$customer || $application->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }

        // Only allow cancel request for approved applications
        if ($application->status !== 'approved') {
            return redirect()->back()->with('error', '承認済みの申請のみキャンセル申請が可能です。');
        }

        // Check if already in cancel process
        if (in_array($application->status, ['cancel_request', 'cancel_approved', 'cancel_rejected'])) {
            return redirect()->back()->with('error', 'この申請は既にキャンセル処理中または処理済みです。');
        }

        $application->update(['status' => 'cancel_request']);

        // 通知を送信
        \App\Services\NotificationService::notifyCancelRequested($application);

        return redirect()->back()->with('status', 'キャンセル申請を送信しました。');
    }
}
