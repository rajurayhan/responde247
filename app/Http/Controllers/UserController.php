<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function show(): JsonResponse
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Debug logging
        \Log::info('Profile update request received', [
            'user_id' => $user->id,
            'request_data' => $request->all(),
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_file' => $request->hasFile('profile_picture'),
            'name_field' => $request->input('name'),
            'name_field_exists' => $request->has('name'),
            'all_inputs' => $request->all(),
            'files' => $request->allFiles()
        ]);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            // Update user data
            $user->name = $request->input('name');
            $user->phone = $request->input('phone');
            $user->company = $request->input('company');
            $user->bio = $request->input('bio');

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                
                // Validate file type
                if (!$file->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file upload'
                    ], 400);
                }
                
                // Delete old profile picture if exists
                if ($user->profile_picture) {
                    $oldPath = str_replace('/storage/', '', $user->profile_picture);
                    Storage::disk('public')->delete($oldPath);
                }

                // Store new profile picture
                $path = $file->store('profile-pictures', 'public');
                $user->profile_picture = $path;
                
                \Log::info('Profile picture uploaded', ['path' => $path]);
            }

            $user->save();

            \Log::info('Profile updated successfully', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string'
        ]);

        $user = Auth::user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    // Admin methods
    public function index(Request $request): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 15); // Default 15 items per page
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $role = $request->get('role', '');
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default ascending

        // Validate pagination parameters
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100
        $page = max($page, 1); // Minimum page 1
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

        // Build query
        $query = User::contentProtection();

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (!empty($status) && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        // Apply role filter
        if (!empty($role) && in_array($role, ['admin', 'user', 'reseller_admin'])) {
            $query->where('role', $role);
        }

        // Apply sorting
        $allowedSortFields = ['name', 'email', 'role', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc'); // Default fallback
        }

        // Get paginated results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'has_more_pages' => $users->hasMorePages(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'role' => $role,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    public function getUsersForAssignment(Request $request): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 50); // Default 50 items per page for assignment
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $role = $request->get('role', '');

        // Validate pagination parameters
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100
        $page = max($page, 1); // Minimum page 1

        // Build query
        $query = User::where('status', 'active')
            ->contentProtection()
            ->select('id', 'name', 'email', 'role');

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if (!empty($role) && in_array($role, ['admin', 'user', 'reseller_admin'])) {
            $query->where('role', $role);
        }

        // Apply sorting
        $query->orderBy('name');

        // Get paginated results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'has_more_pages' => $users->hasMorePages(),
            ],
            'filters' => [
                'search' => $search,
                'role' => $role,
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $resellerId = config('reseller.id');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,reseller_id,' . $resellerId,
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'user', 'reseller_admin'])],
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'reseller_id' => $resellerId
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Ensure user belongs to the same reseller
        if ($user->reseller_id !== Auth::user()->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this user'
            ], 403);
        }

        $resellerId = config('reseller.id');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->where(function ($query) use ($resellerId) {
                return $query->where('reseller_id', $resellerId);
            })->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'user', 'reseller_admin'])],
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Ensure user belongs to the same reseller
        if ($user->reseller_id !== Auth::user()->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this user'
            ], 403);
        }

        // Prevent admin from deleting themselves
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function toggleStatus(User $user): JsonResponse
    {
        // Only admin can access this
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Ensure user belongs to the same reseller
        if ($user->reseller_id !== Auth::user()->reseller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this user'
            ], 403);
        }

        // Prevent admin from deactivating themselves
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate your own account'
            ], 400);
        }

        // Toggle status
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User status updated successfully'
        ]);
    }
}
