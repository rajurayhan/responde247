<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Assistant;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CallLogsProcessor
{
    protected $vapiProcessor;

    public function __construct()
    {
        $this->vapiProcessor = new VapiCallReportProcessor();
    }

    /**
     * Process webhook data and create/update call logs
     */
    public function processWebhook(array $webhookData): ?CallLog
    {
        try {
            $eventType = $webhookData['type'] ?? $webhookData['message']['type'] ?? null;
            
            switch ($eventType) {
                case 'end-of-call-report':
                    return $this->vapiProcessor->processEndCallReport($webhookData);
                case 'call-start':
                    return $this->processCallStart($webhookData);
                case 'call-end':
                    return $this->processCallEnd($webhookData);
                case 'call-update':
                    return $this->processCallUpdate($webhookData);
                default:
                    Log::warning('Unknown webhook event type', ['type' => $eventType]);
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);
            return null;
        }
    }

    /**
     * Process call start event
     */
    private function processCallStart(array $webhookData): ?CallLog
    {
        $callId = $webhookData['callId'] ?? null;
        $assistantId = $webhookData['assistantId'] ?? null;
        
        if (!$callId || !$assistantId) {
            return null;
        }

        $assistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
        if (!$assistant) {
            return null;
        }

        return CallLog::updateOrCreate(
            ['call_id' => $callId],
            [
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'reseller_id' => $assistant->user->reseller_id,
                'phone_number' => $webhookData['phoneNumber'] ?? null,
                'caller_number' => $webhookData['callerNumber'] ?? null,
                'status' => 'initiated',
                'direction' => 'inbound',
                'start_time' => now(),
                'metadata' => $webhookData,
                'webhook_data' => $webhookData,
            ]
        );
    }

    /**
     * Process call end event
     */
    private function processCallEnd(array $webhookData): ?CallLog
    {
        $callId = $webhookData['callId'] ?? null;
        $assistantId = $webhookData['assistantId'] ?? null;
        
        if (!$callId || !$assistantId) {
            return null;
        }

        $assistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
        if (!$assistant) {
            return null;
        }

        $callLog = CallLog::where('call_id', $callId)->first();
        
        if (!$callLog) {
            // Create if doesn't exist
            $callLog = CallLog::create([
                'call_id' => $callId,
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'reseller_id' => $assistant->user->reseller_id,
                'phone_number' => $webhookData['phoneNumber'] ?? null,
                'caller_number' => $webhookData['callerNumber'] ?? null,
                'status' => $this->mapStatus($webhookData['status'] ?? 'completed'),
                'direction' => 'inbound',
                'start_time' => $webhookData['startTime'] ?? now(),
                'end_time' => now(),
                'duration' => $webhookData['duration'] ?? null,
                'transcript' => $webhookData['transcript'] ?? null,
                'summary' => $webhookData['summary'] ?? null,
                'cost' => $webhookData['cost'] ?? null,
                'currency' => $webhookData['currency'] ?? 'USD',
                'metadata' => $webhookData,
                'webhook_data' => $webhookData,
            ]);
        } else {
            // Update existing
            $callLog->update([
                'status' => $this->mapStatus($webhookData['status'] ?? 'completed'),
                'end_time' => now(),
                'duration' => $webhookData['duration'] ?? $callLog->duration,
                'transcript' => $webhookData['transcript'] ?? $callLog->transcript,
                'summary' => $webhookData['summary'] ?? $callLog->summary,
                'cost' => $webhookData['cost'] ?? $callLog->cost,
                'currency' => $webhookData['currency'] ?? $callLog->currency,
                'webhook_data' => $webhookData,
            ]);
        }

        return $callLog;
    }

    /**
     * Process call update event
     */
    private function processCallUpdate(array $webhookData): ?CallLog
    {
        $callId = $webhookData['callId'] ?? null;
        $assistantId = $webhookData['assistantId'] ?? null;
        
        if (!$callId || !$assistantId) {
            return null;
        }

        $callLog = CallLog::where('call_id', $callId)->first();
        if (!$callLog) {
            return null;
        }

        $callLog->update([
            'status' => $this->mapStatus($webhookData['status'] ?? $callLog->status),
            'webhook_data' => $webhookData,
        ]);

        return $callLog;
    }

    /**
     * Map status to internal format
     */
    private function mapStatus(?string $status): string
    {
        $statusMap = [
            'initiated' => 'initiated',
            'ringing' => 'ringing',
            'in-progress' => 'in-progress',
            'completed' => 'completed',
            'failed' => 'failed',
            'busy' => 'busy',
            'no-answer' => 'no-answer',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$status] ?? 'completed';
    }

    /**
     * Extract and save contact information from call log
     */
    public function extractAndSaveContact(CallLog $callLog): ?Contact
    {
        try {
            $contactInfo = $this->vapiProcessor->extractContactInfo($callLog->webhook_data ?? []);
            
            if (empty($contactInfo['name']) && empty($contactInfo['email']) && empty($contactInfo['phone'])) {
                return null;
            }

            // Check if contact already exists
            $existingContact = null;
            if ($contactInfo['email']) {
                $existingContact = Contact::where('email', $contactInfo['email'])->first();
            } elseif ($contactInfo['phone']) {
                $existingContact = Contact::where('phone', $contactInfo['phone'])->first();
            }

            if ($existingContact) {
                // Update existing contact
                $existingContact->update([
                    'name' => $contactInfo['name'] ?: $existingContact->name,
                    'phone' => $contactInfo['phone'] ?: $existingContact->phone,
                    'email' => $contactInfo['email'] ?: $existingContact->email,
                    'company' => $contactInfo['company'] ?: $existingContact->company,
                    'updated_at' => now(),
                ]);
                return $existingContact;
            } else {
                // Create new contact
                return Contact::create([
                    'user_id' => $callLog->user_id,
                    'name' => $contactInfo['name'],
                    'email' => $contactInfo['email'],
                    'phone' => $contactInfo['phone'],
                    'company' => $contactInfo['company'],
                    'source' => 'call_log',
                    'notes' => "Extracted from call {$callLog->call_id}. Inquiry: {$contactInfo['inquiry_type']}",
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error extracting contact from call log', [
                'call_id' => $callLog->call_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get comprehensive call analytics
     */
    public function getCallAnalytics(array $filters = []): array
    {
        $query = CallLog::query();

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['assistant_id'])) {
            $query->where('assistant_id', $filters['assistant_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('start_time', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('start_time', '<=', $filters['end_date']);
        }

        // Basic metrics
        $totalCalls = $query->count();
        $completedCalls = (clone $query)->where('status', 'completed')->count();
        $failedCalls = (clone $query)->whereIn('status', ['failed', 'busy', 'no-answer'])->count();
        $totalCost = (clone $query)->sum('cost');
        $averageDuration = (clone $query)->whereNotNull('duration')->avg('duration');

        // Status breakdown
        $statusBreakdown = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Daily call volume
        $dailyVolume = $query->select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('count(*) as calls'),
                DB::raw('sum(cost) as total_cost'),
                DB::raw('avg(duration) as avg_duration')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Assistant performance
        $assistantPerformance = $query->select(
                'assistant_id',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed_calls'),
                DB::raw('avg(duration) as avg_duration'),
                DB::raw('sum(cost) as total_cost')
            )
            ->groupBy('assistant_id')
            ->get();

        // Call quality metrics
        $qualityMetrics = [
            'success_rate' => $totalCalls > 0 ? round(($completedCalls / $totalCalls) * 100, 2) : 0,
            'failure_rate' => $totalCalls > 0 ? round(($failedCalls / $totalCalls) * 100, 2) : 0,
            'avg_cost_per_call' => $totalCalls > 0 ? round($totalCost / $totalCalls, 4) : 0,
            'avg_duration_minutes' => round(($averageDuration ?? 0) / 60, 2),
        ];

        return [
            'summary' => [
                'total_calls' => $totalCalls,
                'completed_calls' => $completedCalls,
                'failed_calls' => $failedCalls,
                'total_cost' => $totalCost,
                'average_duration' => round($averageDuration ?? 0),
            ],
            'status_breakdown' => $statusBreakdown,
            'daily_volume' => $dailyVolume,
            'assistant_performance' => $assistantPerformance,
            'quality_metrics' => $qualityMetrics,
        ];
    }

    /**
     * Get call trends over time
     */
    public function getCallTrends(array $filters = [], int $days = 30): array
    {
        $query = CallLog::query();

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['assistant_id'])) {
            $query->where('assistant_id', $filters['assistant_id']);
        }

        $startDate = Carbon::now()->subDays($days);
        $query->where('start_time', '>=', $startDate);

        // Get daily trends
        $dailyTrends = $query->select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed_calls'),
                DB::raw('sum(case when status in ("failed", "busy", "no-answer") then 1 else 0 end) as failed_calls'),
                DB::raw('sum(cost) as total_cost'),
                DB::raw('avg(duration) as avg_duration')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get hourly trends for the last 7 days
        $hourlyTrends = $query->where('start_time', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('HOUR(start_time) as hour'),
                DB::raw('count(*) as calls'),
                DB::raw('avg(duration) as avg_duration')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'daily_trends' => $dailyTrends,
            'hourly_trends' => $hourlyTrends,
        ];
    }

    /**
     * Export call logs for analysis
     */
    public function exportCallLogs(array $filters = []): array
    {
        $query = CallLog::with(['assistant', 'user']);

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['assistant_id'])) {
            $query->where('assistant_id', $filters['assistant_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('start_time', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('start_time', '<=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('start_time', 'desc')->get()->toArray();
    }
} 