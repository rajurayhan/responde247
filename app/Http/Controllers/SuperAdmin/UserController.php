<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Reseller;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of all users across all resellers
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $resellerId = $request->get('reseller_id');
        $role = $request->get('role');
        $status = $request->get('status');
        $includeDeleted = $request->boolean('include_deleted', false);

        $query = User::with(['reseller:id,org_name', 'activeSubscription.package:id,name'])
            ->withCount(['assistants', 'subscriptions']);

        // Apply soft delete filter
        if ($includeDeleted) {
            $query->withTrashed();
        }

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($resellerId) {
            $query->where('reseller_id', $resellerId);
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform the data
        $transformedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'phone' => $user->phone,
                'company' => $user->company,
                'reseller' => $user->reseller ? [
                    'id' => $user->reseller->id,
                    'org_name' => $user->reseller->org_name,
                ] : null,
                'assistants_count' => $user->assistants_count,
                'subscriptions_count' => $user->subscriptions_count,
                'has_active_subscription' => $user->activeSubscription ? true : false,
                'subscription_package' => $user->activeSubscription?->package ? [
                    'id' => $user->activeSubscription->package->id,
                    'name' => $user->activeSubscription->package->name,
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'deleted_at' => $user->deleted_at,
                'is_deleted' => !is_null($user->deleted_at),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedUsers,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => [
                'resellers' => Reseller::select('id', 'org_name')->orderBy('org_name')->get(),
                'roles' => ['user', 'admin', 'content_admin', 'reseller_admin'],
                'statuses' => ['active', 'inactive'],
            ]
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:user,admin,content_admin,reseller_admin',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'status' => 'required|string|in:active,inactive',
            'reseller_id' => 'nullable|exists:resellers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'company' => $request->company,
                'bio' => $request->bio,
                'status' => $request->status,
                'reseller_id' => $request->reseller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load(['reseller:id,org_name'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show($id)
    {
        $user = User::withTrashed()
            ->with([
                'reseller:id,org_name,company_email',
                'subscriptions.package:id,name,price',
                'assistants:id,name,phone_number,type',
                'transactions:id,amount,status,created_at'
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'statistics' => [
                    'total_assistants' => $user->assistants()->count(),
                    'total_subscriptions' => $user->subscriptions()->count(),
                    'total_transactions' => $user->transactions()->count(),
                    'total_spent' => $user->transactions()->where('status', 'completed')->sum('amount'),
                ]
            ]
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:user,admin,content_admin,reseller_admin',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'status' => 'sometimes|string|in:active,inactive',
            'reseller_id' => 'nullable|exists:resellers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only([
                'name', 'email', 'role', 'phone', 'company', 'bio', 'status', 'reseller_id'
            ]);

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->load(['reseller:id,org_name'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete the specified user
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted user
     */
    public function restore($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            return response()->json([
                'success' => true,
                'message' => 'User restored successfully',
                'data' => $user->load(['reseller:id,org_name'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a user
     */
    public function forceDelete($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'User permanently deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics for dashboard
     */
    public function statistics()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $deletedUsers = User::onlyTrashed()->count();
        $usersByRole = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        $usersByReseller = User::with('reseller:id,org_name')
            ->selectRaw('reseller_id, COUNT(*) as count')
            ->groupBy('reseller_id')
            ->get()
            ->map(function ($item) {
                return [
                    'reseller_name' => $item->reseller ? $item->reseller->org_name : 'No Reseller',
                    'count' => $item->count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'deleted_users' => $deletedUsers,
                'users_by_role' => $usersByRole,
                'users_by_reseller' => $usersByReseller,
            ]
        ]);
    }
}