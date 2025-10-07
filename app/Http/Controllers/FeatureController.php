<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeatureController extends Controller
{
    /**
     * Get all active features
     */
    public function index(): JsonResponse
    {
        // For public access, get features based on reseller context
        $resellerId = config('reseller.id');
        
        if ($resellerId) {
            $features = Feature::where('reseller_id', $resellerId)
                ->active()
                ->ordered()
                ->get();
        } else {
            // If no reseller context, return empty result
            $features = collect();
        }

        return response()->json([
            'success' => true,
            'data' => $features
        ]);
    }

    /**
     * Admin: Get all features
     */
    public function adminIndex(): JsonResponse
    {
        $features = Feature::contentProtection()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $features
        ]);
    }

    /**
     * Admin: Create a new feature
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $feature = Feature::create(array_merge($request->all(), [
            'reseller_id' => Auth::user()->reseller_id,
            'user_id' => Auth::user()->id
        ]));

        return response()->json([
            'success' => true,
            'data' => $feature,
            'message' => 'Feature created successfully'
        ], 201);
    }

    /**
     * Admin: Update a feature
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $feature = Feature::contentProtection()->findOrFail($id);
        $feature->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $feature,
            'message' => 'Feature updated successfully'
        ]);
    }

    /**
     * Admin: Delete a feature
     */
    public function destroy($id): JsonResponse
    {
        $feature = Feature::contentProtection()->findOrFail($id);
        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature deleted successfully'
        ]);
    }
}
