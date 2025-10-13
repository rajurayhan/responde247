<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemoRequestController extends Controller
{
    /**
     * Submit a demo request
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'country' => 'required|string|in:United States,Mexico',
            'services' => 'required|string|max:1000',
        ]);

        try {
            // Get reseller from domain context
            $reseller = app('currentReseller');
            
            $demoRequest = DemoRequest::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'industry' => $request->industry,
                'country' => $request->country,
                'services' => $request->services,
                'status' => 'pending',
                'reseller_id' => $reseller ? $reseller->id : null,
                'user_id' => null // Public demo request form, no user associated
            ]);

            Log::info('Demo request submitted', [
                'id' => $demoRequest->id,
                'name' => $demoRequest->name,
                'email' => $demoRequest->email,
                'company' => $demoRequest->company_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demo request submitted successfully! We will contact you within 24 hours.',
                'data' => $demoRequest
            ], 201);
        } catch (\Exception $e) {
            Log::error('Demo request submission failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit demo request. Please try again.'
            ], 500);
        }
    }

    /**
     * Check if user has already requested a demo
     */
    public function checkExistingRequest(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Get reseller from domain context
        $reseller = app('currentReseller');
        
        $existingRequest = DemoRequest::where('email', $request->email)
            ->where('reseller_id', $reseller ? $reseller->id : null)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'has_requested' => $existingRequest !== null,
                'request' => $existingRequest
            ]
        ]);
    }

    /**
     * Admin: Get all demo requests with filtering
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = DemoRequest::contentProtection();

        // Filter by status
        if ($request->has('status') && $request->status !== '' && $request->status !== null && $request->status !== 'null') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from && $request->date_from !== '' && $request->date_from !== 'null') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to && $request->date_to !== '' && $request->date_to !== 'null') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by name, email, or company
        if ($request->has('search') && $request->search && $request->search !== '' && $request->search !== 'null') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $demoRequests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $demoRequests
        ]);
    }

    /**
     * Admin: Get demo request statistics
     */
    public function adminStats(Request $request): JsonResponse
    {
        $query = DemoRequest::contentProtection();

        // Apply date filters only if they are not empty/null
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if ($dateFrom && $dateFrom !== '' && $dateFrom !== 'null') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo && $dateTo !== '' && $dateTo !== 'null') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // No default date range - show all records if no date filters are applied

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'contacted' => (clone $query)->contacted()->count(),
            'completed' => (clone $query)->completed()->count(),
            'cancelled' => (clone $query)->cancelled()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Admin: Update demo request status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,contacted,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $demoRequest = DemoRequest::contentProtection()->findOrFail($id);

        $updateData = [
            'status' => $request->status,
        ];

        // Update timestamps based on status
        if ($request->status === 'contacted' && $demoRequest->status !== 'contacted') {
            $updateData['contacted_at'] = now();
        } elseif ($request->status === 'completed' && $demoRequest->status !== 'completed') {
            $updateData['completed_at'] = now();
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        $demoRequest->update($updateData);

        Log::info('Demo request status updated', [
            'id' => $demoRequest->id,
            'status' => $request->status,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demo request status updated successfully',
            'data' => $demoRequest->fresh()
        ]);
    }

    /**
     * Admin: Get single demo request
     */
    public function show($id): JsonResponse
    {
        $demoRequest = DemoRequest::contentProtection()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $demoRequest
        ]);
    }

    /**
     * Admin: Delete demo request
     */
    public function destroy($id): JsonResponse
    {
        $demoRequest = DemoRequest::contentProtection()->findOrFail($id);
        $demoRequest->delete();

        Log::info('Demo request deleted', [
            'id' => $demoRequest->id,
            'deleted_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demo request deleted successfully'
        ]);
    }
} 