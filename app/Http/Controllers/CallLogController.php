<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CallLog;
use App\Models\Assistant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CallLogController extends Controller
{
    /**
     * Get call logs with filtering (lightweight for list view)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CallLog::contentProtection();

        // Apply filters
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('call_id', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
                // Removed transcript search from list view for performance
            });
        }

        // Order by most recent first
        $query->orderBy('start_time', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $callLogs = $query->paginate($perPage);

        // Filter out sensitive data for non-admin users
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isContentAdmin() ?? false;
        $callLogsData = $callLogs->items();
        
        if (!$isAdmin) {
            $callLogsData = collect($callLogsData)->map(function ($callLog) {
                unset($callLog->webhook_data);
                unset($callLog->metadata);
                unset($callLog->cost);
                unset($callLog->currency);
                unset($callLog->transcript); // Remove transcript from list view
                return $callLog;
            })->toArray();
        } else {
            // For admin users, still remove transcript from list view for performance
            $callLogsData = collect($callLogsData)->map(function ($callLog) {
                unset($callLog->transcript);
                return $callLog;
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $callLogsData,
            'meta' => [
                'current_page' => $callLogs->currentPage(),
                'last_page' => $callLogs->lastPage(),
                'per_page' => $callLogs->perPage(),
                'total' => $callLogs->total(),
                'from' => $callLogs->firstItem(),
                'to' => $callLogs->lastItem(),
            ]
        ]);
    }

    /**
     * Get lightweight call logs for list view (optimized)
     */
    public function list(Request $request)
    {
        $user = $request->user();
        
        // Use select to only get needed fields for list view
        $query = CallLog::contentProtection()->select([
            'id',
            'call_id',
            'assistant_id',
            'phone_number',
            'caller_number',
            'duration',
            'status',
            'direction',
            'start_time',
            'end_time',
            'summary',
            'cost',
            'currency'
        ]);

        // Apply filters
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('call_id', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        // Order by most recent first
        $query->orderBy('start_time', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 20); // Increased default page size
        $callLogs = $query->paginate($perPage);

        // Filter out sensitive data for non-admin users
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isContentAdmin() ?? false;
        $callLogsData = $callLogs->items();
        
        if (!$isAdmin) {
            $callLogsData = collect($callLogsData)->map(function ($callLog) {
                unset($callLog->cost);
                unset($callLog->currency);
                return $callLog;
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $callLogsData,
            'meta' => [
                'current_page' => $callLogs->currentPage(),
                'last_page' => $callLogs->lastPage(),
                'per_page' => $callLogs->perPage(),
                'total' => $callLogs->total(),
                'from' => $callLogs->firstItem(),
                'to' => $callLogs->lastItem(),
            ]
        ]);
    }

    /**
     * Search call logs with full-text search (for detailed search)
     */
    public function search(Request $request)
    {
        $user = $request->user();
        
        if (!$request->filled('search') || strlen($request->search) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 3 characters'
            ], 400);
        }

        $search = $request->search;
        
        // Use select to only get needed fields for search results
        $query = CallLog::contentProtection()->select([
            'id',
            'call_id',
            'assistant_id',
            'phone_number',
            'caller_number',
            'duration',
            'status',
            'direction',
            'start_time',
            'end_time',
            'summary',
            'cost',
            'currency'
        ]);

        // Apply other filters
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Full-text search on call_id, summary, and transcript
        $query->where(function ($q) use ($search) {
            $q->where('call_id', 'like', "%{$search}%")
              ->orWhere('summary', 'like', "%{$search}%")
              ->orWhere('transcript', 'like', "%{$search}%");
        });

        // Order by relevance (call_id matches first, then summary, then transcript)
        $query->orderByRaw("
            CASE 
                WHEN call_id LIKE ? THEN 1
                WHEN summary LIKE ? THEN 2
                WHEN transcript LIKE ? THEN 3
                ELSE 4
            END", ["%{$search}%", "%{$search}%", "%{$search}%"])
            ->orderBy('start_time', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $callLogs = $query->paginate($perPage);

        // Filter out sensitive data for non-admin users
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isContentAdmin() ?? false;
        $callLogsData = $callLogs->items();
        
        if (!$isAdmin) {
            $callLogsData = collect($callLogsData)->map(function ($callLog) {
                unset($callLog->cost);
                unset($callLog->currency);
                return $callLog;
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $callLogsData,
            'meta' => [
                'current_page' => $callLogs->currentPage(),
                'last_page' => $callLogs->lastPage(),
                'per_page' => $callLogs->perPage(),
                'total' => $callLogs->total(),
                'from' => $callLogs->firstItem(),
                'to' => $callLogs->lastItem(),
            ]
        ]);
    }

    /**
     * Get call logs statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $query = CallLog::contentProtection();

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Apply assistant filter
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        // Get basic stats
        $totalCalls = $query->count();
        $completedCalls = (clone $query)->where('status', 'completed')->count();
        $inboundCalls = (clone $query)->where('direction', 'inbound')->count();
        $outboundCalls = (clone $query)->where('direction', 'outbound')->count();
        $averageDuration = (clone $query)->whereNotNull('duration')->avg('duration');
        $totalDuration = (clone $query)->whereNotNull('duration')->sum('duration');
        
        // Only get cost data for admin users
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isContentAdmin() ?? false;
        $totalCost = $isAdmin ? (clone $query)->sum('cost') : 0;

        // Get status breakdown
        $statusBreakdown = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get assistant performance
        $assistantPerformanceQuery = $query->select(
                'assistant_id',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed_calls'),
                DB::raw('avg(duration) as avg_duration')
            );
        
        // Only include cost for admin users
        if ($isAdmin) {
            $assistantPerformanceQuery->addSelect(DB::raw('sum(cost) as total_cost'));
        }
        
        $assistantPerformance = $assistantPerformanceQuery->groupBy('assistant_id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totalCalls' => $totalCalls,
                'completedCalls' => $completedCalls,
                'totalDuration' => (int) ($totalDuration ?? 0),
                'inboundCalls' => $inboundCalls,
                'outboundCalls' => $outboundCalls,
                'totalCost' => $totalCost,
                'averageDuration' => round($averageDuration ?? 0),
                'statusBreakdown' => $statusBreakdown,
                'assistantPerformance' => $assistantPerformance
            ]
        ]);
    }

    /**
     * Get specific call log details
     */
    public function show(Request $request, $callId)
    {
        $user = $request->user();
        
        // Get call log using content protection
        $callLog = CallLog::contentProtection()
            ->where('call_id', $callId)
            ->first();

        if (!$callLog) {
            return response()->json([
                'success' => false,
                'message' => 'Call log not found'
            ], 404);
        }

        // Filter out sensitive data for non-admin users
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isContentAdmin() ?? false;
        $callLogData = $callLog->toArray();
        
        if (!$isAdmin) {
            unset($callLogData['webhook_data']);
            unset($callLogData['metadata']);
            unset($callLogData['cost']);
            unset($callLogData['currency']);
        }

        // Generate Conversation from transcript
        $conversation = $this->generateConversationFromTranscript($callLog->transcript);

        return response()->json([
            'success' => true,
            'data' => $callLogData,
            'conversation' => $conversation
        ]);
    }

    /**
     * Generate structured conversation JSON from transcript
     */
    private function generateConversationFromTranscript($transcript)
    {
        if (empty($transcript)) {
            return [
                'conversation' => [],
                'total_messages' => 0,
                'participants' => []
            ];
        }

        $lines = explode("\n", $transcript);
        $conversation = [];
        $participants = [];
        $messageIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse "Speaker: Message" format
            if (preg_match('/^(AI|User|Assistant|Customer|Bot):\s*(.+)$/', $line, $matches)) {
                $speaker = $matches[1];
                $message = $matches[2];

                // Normalize speaker names
                $normalizedSpeaker = match($speaker) {
                    'AI', 'Assistant', 'Bot' => 'Assistant',
                    'User', 'Customer' => 'Customer',
                    default => $speaker
                };

                // Track participants
                if (!in_array($normalizedSpeaker, $participants)) {
                    $participants[] = $normalizedSpeaker;
                }

                $conversation[] = [
                    'id' => ++$messageIndex,
                    'speaker' => $normalizedSpeaker,
                    'message' => $message,
                    'timestamp' => null, // Could be calculated if needed
                    'original_speaker' => $speaker
                ];
            }
        }

        return [
            'conversation' => $conversation,
            'total_messages' => count($conversation),
            'participants' => $participants,
            'summary' => [
                'assistant_messages' => count(array_filter($conversation, fn($msg) => $msg['speaker'] === 'Assistant')),
                'customer_messages' => count(array_filter($conversation, fn($msg) => $msg['speaker'] === 'Customer'))
            ]
        ];
    }
} 