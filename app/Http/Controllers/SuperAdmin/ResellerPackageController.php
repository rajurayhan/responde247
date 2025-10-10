<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ResellerPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResellerPackageController extends Controller
{
    /**
     * Display a listing of reseller packages
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ResellerPackage::query();

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Apply reseller filter
            if ($request->has('reseller_id') && !empty($request->reseller_id)) {
                $query->where('reseller_id', $request->reseller_id);
            }

            $packages = $query->orderBy('created_at', 'desc')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $packages
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller packages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reseller packages'
            ], 500);
        }
    }

    /**
     * Store a newly created reseller package
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'voice_agents_limit' => 'required|integer|min:-1',
                'monthly_minutes_limit' => 'required|integer|min:-1',
                'extra_per_minute_charge' => 'required|numeric|min:0',
                'features' => 'nullable|array',
                'support_level' => 'required|string|in:standard,priority,enterprise',
                'analytics_level' => 'required|string|in:basic,advanced,enterprise',
                'is_popular' => 'boolean',
                'is_active' => 'boolean',
            ]);

            $package = ResellerPackage::create($validated);

            return response()->json([
                'success' => true,
                'data' => $package,
                'message' => 'Reseller package created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating reseller package: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating reseller package'
            ], 500);
        }
    }

    /**
     * Display the specified reseller package
     */
    public function show(ResellerPackage $resellerPackage): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $resellerPackage
        ]);
    }

    /**
     * Update the specified reseller package
     */
    public function update(Request $request, ResellerPackage $resellerPackage): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'voice_agents_limit' => 'sometimes|required|integer|min:-1',
                'monthly_minutes_limit' => 'sometimes|required|integer|min:-1',
                'extra_per_minute_charge' => 'sometimes|required|numeric|min:0',
                'features' => 'nullable|array',
                'support_level' => 'sometimes|required|string|in:standard,priority,enterprise',
                'analytics_level' => 'sometimes|required|string|in:basic,advanced,enterprise',
                'is_popular' => 'boolean',
                'is_active' => 'boolean',
            ]);

            $resellerPackage->update($validated);

            return response()->json([
                'success' => true,
                'data' => $resellerPackage,
                'message' => 'Reseller package updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating reseller package: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating reseller package'
            ], 500);
        }
    }

    /**
     * Remove the specified reseller package
     */
    public function destroy(ResellerPackage $resellerPackage): JsonResponse
    {
        try {
            // Check if package has active subscriptions
            if ($resellerPackage->subscriptions()->whereIn('status', ['active', 'pending'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete package with active subscriptions'
                ], 400);
            }

            $resellerPackage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reseller package deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting reseller package: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting reseller package'
            ], 500);
        }
    }

    /**
     * Toggle package status
     */
    public function toggleStatus(ResellerPackage $resellerPackage): JsonResponse
    {
        try {
            $resellerPackage->update(['is_active' => !$resellerPackage->is_active]);

            return response()->json([
                'success' => true,
                'data' => $resellerPackage,
                'message' => 'Package status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling package status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating package status'
            ], 500);
        }
    }
}
