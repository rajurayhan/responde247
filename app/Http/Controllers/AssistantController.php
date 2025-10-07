<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Services\VapiService;
use App\Http\Requests\VapiAssistantRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Added this import for User model

class AssistantController extends Controller
{
    protected $vapiService;

    public function __construct(VapiService $vapiService)
    {
        $this->vapiService = $vapiService;
    }

    /**
     * Get all assistants for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = Assistant::with(['user', 'creator'])->contentProtection();
        
        
        // Search by name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Search by phone number
        if ($request->has('phone_search') && !empty($request->phone_search)) {
            $phoneSearch = $request->phone_search;
            $query->where('phone_number', 'like', "%{$phoneSearch}%");
        }
        
        // Sort by name (default) or other fields
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['name', 'created_at', 'user_id'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc'); // Default fallback
        }
        
        // Pagination
        $perPage = $request->get('per_page', 9);
        $page = $request->get('page', 1);
        
        $assistants = $query->paginate($perPage, ['*'], 'page', $page);

        // Return paginated data
        return response()->json([
            'success' => true,
            'data' => $assistants->items(),
            'pagination' => [
                'current_page' => $assistants->currentPage(),
                'last_page' => $assistants->lastPage(),
                'per_page' => $assistants->perPage(),
                'total' => $assistants->total(),
                'from' => $assistants->firstItem(),
                'to' => $assistants->lastItem(),
            ]
        ]);
    }

    /**
     * Get all assistants (admin only)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Assistant::with(['user', 'creator'])->contentProtection();
        
        // Search by assistant name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Search by user (owner) name or email
        if ($request->filled('user_search')) {
            $userSearch = $request->user_search;
            $query->whereHas('user', function ($q) use ($userSearch) {
                $q->where('name', 'like', "%{$userSearch}%")
                  ->orWhere('email', 'like', "%{$userSearch}%");
            });
        }
        
        // Search by phone number
        if ($request->filled('phone_search')) {
            $phoneSearch = $request->phone_search;
            $query->where('phone_number', 'like', "%{$phoneSearch}%");
        }
        
        // Filter by type
        if ($request->filled('type')) {
            $type = $request->type;
            if (in_array($type, ['demo', 'production'])) {
                $query->where('type', $type);
            }
        }
        
        // Sort by name (default) or other fields
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['name', 'created_at', 'user_id'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc'); // Default fallback
        }
        
        // Pagination
        $perPage = $request->get('per_page', 9);
        $page = $request->get('page', 1);
        
        $assistants = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $assistants->items(),
            'pagination' => [
                'current_page' => $assistants->currentPage(),
                'last_page' => $assistants->lastPage(),
                'per_page' => $assistants->perPage(),
                'total' => $assistants->total(),
                'from' => $assistants->firstItem(),
                'to' => $assistants->lastItem(),
            ]
        ]);
    }

    /**
     * Create a new assistant
     */
    public function store(VapiAssistantRequest $request): JsonResponse
    {

        $user = Auth::user();
        
        // Check if user can create more assistants (skip for super admin users only)
        if (!$user->isSuperAdmin()) {
            if (!$user->canCreateAssistant()) {
                $validationMessage = $user->getAssistantCreationValidationMessage();
                return response()->json([
                    'success' => false,
                    'message' => $validationMessage
                ], 403);
            }
        }
        
        // Determine the user_id for the assistant
        $assistantUserId = $user->id; // Default to current user
        
        if ($user->isContentAdmin() && $request->has('user_id') && $request->user_id) {
            // If admin is creating and has specified a user_id, use that
            $assistantUserId = $request->user_id;
        }
        
        // Add user_id to metadata
        $data = $request->all();
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }
        $data['metadata']['user_id'] = $assistantUserId;
        $data['metadata']['user'] = Auth::user()->name;
        // $data['voice']['voiceId'] = 'Spencer';

        // Handle phone number purchase and assignment
        $phoneNumber = null;
        if ($request->has('selected_phone_number') && $request->selected_phone_number) {
            $twilioService = app(\App\Services\TwilioService::class);
            
            // Determine country code from metadata
            $countryCode = null;
            if ($request->has('metadata.country')) {
                $countryCodeMap = [
                    'United States' => 'US',
                    'Canada' => 'CA',
                    'Australia' => 'AU',
                    'United Kingdom' => 'GB'
                ];
                $countryCode = $countryCodeMap[$request->input('metadata.country')] ?? null;
            }
            
            // Purchase the selected phone number
            $purchaseResult = $twilioService->purchaseNumber($request->selected_phone_number, $countryCode);
            
            if ($purchaseResult['success']) {
                $phoneNumber = $request->selected_phone_number;
                $data['metadata']['assistant_phone_number'] = $phoneNumber;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to purchase phone number: ' . ($purchaseResult['message'] ?? 'Unknown error')
                ], 500);
            }
        }

        // Create assistant in Vapi
        $vapiAssistant = $this->vapiService->createAssistant($data);

        if (!$vapiAssistant) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assistant in Cloud. Please try again.'
            ], 500);
        }

        // Store in database with all Vapi API fields
        $assistantData = [
            'name' => $data['name'],
            'user_id' => $assistantUserId,
            'vapi_assistant_id' => $vapiAssistant['id'],
            'created_by' => $user->id,
            'reseller_id' => $user->reseller_id,
            'type' => $data['type'] ?? 'demo', // Default to demo
            'phone_number' => $data['metadata']['assistant_phone_number'] ?? null,
            'webhook_url' => $data['metadata']['webhook_url'] ?? 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents',
        ];

        // Add all optional Vapi API fields to database
        $optionalFields = [
            'transcriber', 'model', 'voice', 'firstMessage', 'firstMessageInterruptionsEnabled',
            'firstMessageMode', 'voicemailDetection', 'clientMessages', 'serverMessages',
            'maxDurationSeconds', 'backgroundSound', 'modelOutputInMessagesEnabled',
            'transportConfigurations', 'observabilityPlan', 'credentialIds', 'credentials',
            'hooks', 'voicemailMessage', 'endCallMessage', 'endCallPhrases', 'compliancePlan',
            'backgroundSpeechDenoisingPlan', 'analysisPlan', 'artifactPlan', 'startSpeakingPlan', 
            'stopSpeakingPlan', 'monitorPlan', 'keypadInputPlan'
        ];

        foreach ($optionalFields as $field) {
            if (isset($data[$field])) {
                $assistantData[$field] = $data[$field];
            }
        }

        $assistant = Assistant::create($assistantData);

        // Assign phone number to Vapi if purchased
        if ($phoneNumber) {
            $this->vapiService->assignPhoneNumber($vapiAssistant['id'], $phoneNumber);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($assistant->toArray(), [
                'vapi_data' => $vapiAssistant
            ]),
            'message' => 'Assistant created successfully'
        ], 201);
    }

    /**
     * Get a specific assistant
     */
    public function show(Request $request, $assistantId): JsonResponse
    {
        $user = Auth::user();
        
        // Find assistant in database
        $assistantQuery = Assistant::where('vapi_assistant_id', $assistantId)->contentProtection();

        $assistant = $assistantQuery->with(['user', 'creator'])->first();

        if (!$assistant) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }


        // Get detailed data from Vapi
        $vapiData = $this->vapiService->getAssistant($assistant->vapi_assistant_id);

        // Synchronize webhook URL between Vapi and local database
        $vapiWebhookUrl = null;
        if (isset($vapiData['server']['url'])) {
            $vapiWebhookUrl = $vapiData['server']['url'];
        }

        // If Vapi has webhook URL but local database doesn't, use Vapi's value
        if ($vapiWebhookUrl) {
            $assistant->update(['webhook_url' => $vapiWebhookUrl]);
        }

        // Synchronize type from Vapi metadata to local database
        $vapiType = null;
        if (isset($vapiData['metadata']['type'])) {
            $vapiType = $vapiData['metadata']['type'];
        }

        // If Vapi has type but local database doesn't, or if they differ, sync from Vapi
        if ($vapiType && $assistant->type !== $vapiType) {
            $assistant->update(['type' => $vapiType]);
        }

        // Merge webhook URL from database with Vapi metadata for frontend
        if ($assistant->webhook_url && isset($vapiData['metadata'])) {
            $vapiData['metadata']['webhook_url'] = $assistant->webhook_url;
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($assistant->toArray(), [
                'vapi_data' => $vapiData
            ])
        ]);
    }

    /**
     * Update an assistant
     */
    public function update(VapiAssistantRequest $request, $assistantId): JsonResponse
    {

        $user = Auth::user();
        
        // Find assistant in database
        $assistantQuery = Assistant::where('vapi_assistant_id', $assistantId)
            ->contentProtection();

            $assistant = $assistantQuery->first();

        if (!$assistant) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        // Update in Vapi - VapiService will handle preserving all existing data
        $vapiData = $this->vapiService->updateAssistant($assistant->vapi_assistant_id, $request->all());

        if (!$vapiData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assistant in Vapi'
            ], 500);
        }

        // Prepare update data for local database
        $updateData = [
            'name' => $request->name,
        ];
        
        // Synchronize webhook URL from Vapi response
        $vapiWebhookUrl = null;
        if (isset($vapiData['server']['url'])) {
            $vapiWebhookUrl = $vapiData['server']['url'];
        }

        // If Vapi has webhook URL but local database doesn't, use Vapi's value
        if ($vapiWebhookUrl && !$assistant->webhook_url) {
            $updateData['webhook_url'] = $vapiWebhookUrl;
        }
        
        // If admin is updating and has specified a new user_id, update it
        // if ($user->isAdmin() && $request->has('user_id') && $request->user_id && $request->user_id != $assistant->user_id) {
        //     $updateData['user_id'] = $request->user_id;
        // }
        
        // Update type if provided
        if ($request->has('type')) {
            $updateData['type'] = $request->type;
        }
        
        // Update phone_number if provided
        if ($request->has('metadata.assistant_phone_number')) {
            $updateData['phone_number'] = $request->input('metadata.assistant_phone_number');
        }
        
        // Update webhook_url if provided
        if ($request->has('metadata.webhook_url')) {
            $updateData['webhook_url'] = $request->input('metadata.webhook_url');
        }

        // Add all optional Vapi API fields to database update
        $optionalFields = [
            'transcriber', 'model', 'voice', 'firstMessage', 'firstMessageInterruptionsEnabled',
            'firstMessageMode', 'voicemailDetection', 'clientMessages', 'serverMessages',
            'maxDurationSeconds', 'backgroundSound', 'modelOutputInMessagesEnabled',
            'transportConfigurations', 'observabilityPlan', 'credentialIds', 'credentials',
            'hooks', 'voicemailMessage', 'endCallMessage', 'endCallPhrases', 'compliancePlan',
            'backgroundSpeechDenoisingPlan', 'analysisPlan', 'artifactPlan', 'startSpeakingPlan', 
            'stopSpeakingPlan', 'monitorPlan', 'keypadInputPlan'
        ];

        foreach ($optionalFields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        // Update in database
        $assistant->update($updateData);

        return response()->json([
            'success' => true,
            'data' => array_merge($assistant->toArray(), [
                'vapi_data' => $vapiData
            ]),
            'message' => 'Assistant updated successfully'
        ]);
    }

    /**
     * Delete an assistant
     */
    public function destroy(Request $request, $assistantId): JsonResponse
    {
        $user = Auth::user();
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)->contentProtection()
            // ->orWhere('id', $assistantId)
            ->first();

        if (!$assistant) {
            Log::warning('Assistant not found for deletion', ['assistant_id' => $assistantId]);
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        // Check if user owns this assistant or is admin
        // if (!$user->isAdmin() && $assistant->user_id != $user->id) {
        //     Log::warning('Unauthorized delete attempt', [
        //         'user_id' => $user->id,
        //         'assistant_user_id' => $assistant->user_id,
        //         'assistant_id' => $assistantId
        //     ]);
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized to delete this assistant'
        //     ], 403);
        // }

        try {
            // Delete from Vapi first
            $vapiSuccess = $this->vapiService->deleteAssistant($assistant->vapi_assistant_id);

            if (!$vapiSuccess) {
                Log::error('Failed to delete assistant from Vapi', [
                    'vapi_assistant_id' => $assistant->vapi_assistant_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete assistant from Vapi server. Please try again.'
                ], 500);
            }

            // Delete from database
            $assistant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assistant deleted successfully from both system and Vapi server'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Exception during assistant deletion', [
                'assistant_id' => $assistant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the assistant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assistant statistics
     */
    public function stats(Request $request, $assistantId): JsonResponse
    {
        $user = Auth::user();
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)->contentProtection()
            // ->orWhere('id', $assistantId)
            ->first();

        if (!$assistant) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        $stats = $this->vapiService->getAssistantStats($assistant->vapi_assistant_id);

        if (!$stats) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get assistant statistics'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 