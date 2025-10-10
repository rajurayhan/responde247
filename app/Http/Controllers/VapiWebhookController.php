<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CallLog;
use App\Models\Assistant;
use App\Services\VapiCallReportProcessor;
use App\Services\ResellerUsageTracker;

class VapiWebhookController extends Controller
{
    /**
     * Handle incoming webhook events from Vapi.ai
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Vapi webhook received', [
                'type' => $payload['message']['type'] ?? 'unknown',
                'callId' => $payload['message']['call']['id'] ?? 'unknown',
                'assistantId' => $payload['message']['assistant']['id'] ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);

            $eventType = $payload['message']['type'] ?? null;
            $callId = $payload['message']['call']['id']?? null;
            $assistantId = $payload['message']['assistant']['id'] ?? null;

            if (!$eventType || !$callId || !$assistantId) {
                Log::warning('Missing required fields in webhook payload', [
                    'payload' => $payload,
                    'missing_fields' => [
                        'type' => !$eventType,
                        'callId' => !$callId,
                        'assistantId' => !$assistantId
                    ]
                ]);
                return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
            }

            // Find the assistant
            $assistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
            if (!$assistant) {
                Log::warning('Assistant not found for vapi_assistant_id', [
                    'assistant_id' => $assistantId,
                    'available_assistants' => Assistant::pluck('vapi_assistant_id')->toArray()
                ]);
                return response()->json(['success' => false, 'message' => 'Assistant not found'], 404);
            }

            // Handle different event types and get call log data
            $callLog = null;
            switch ($eventType) {
                case 'call-start':
                    $callLog = $this->handleCallStart($payload, $assistant);
                    break;
                case 'call-end':
                    $callLog = $this->handleCallEnd($payload, $assistant);
                    break;
                case 'call-update':
                    $callLog = $this->handleCallUpdate($payload, $assistant);
                    break;
                case 'end-of-call-report':
                    $callLog = $this->handleEndOfCallReport($payload, $assistant);
                    break;
                default:
                    Log::warning('Unknown event type', [
                        'type' => $eventType,
                        'callId' => $callId,
                        'assistantId' => $assistantId,
                        'supported_types' => ['call-start', 'call-end', 'call-update', 'end-of-call-report']
                    ]);
                    return response()->json(['success' => false, 'message' => 'Unknown event type'], 400);
            }

            Log::info('Vapi webhook processed successfully', [
                'type' => $eventType,
                'callId' => $callId,
                'assistantId' => $assistantId,
                'assistant_name' => $assistant->name
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $callLog ? $this->formatCallLogResponse($callLog) : null,
                'event_type' => $eventType,
                'call_id' => $callId,
                'assistant_id' => $assistantId
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle call start event
     */
    private function handleCallStart(array $payload, Assistant $assistant): ?CallLog
    {
        $callLog = CallLog::updateOrCreate(
            ['call_id' => $payload['callId']],
            [
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'reseller_id' => $assistant->user->reseller_id,
                'phone_number' => $payload['phoneNumber'] ?? null,
                'caller_number' => $payload['callerNumber'] ?? null,
                'status' => $this->mapVapiStatus($payload['status'] ?? 'initiated'),
                'direction' => $payload['direction'] ?? 'inbound',
                'start_time' => now(),
                'metadata' => $payload,
                'webhook_data' => $payload,
            ]
        );

        Log::info('Call log created/updated for call start', [
            'call_id' => $callLog->call_id,
            'assistant_id' => $assistant->id
        ]);

        return $callLog;
    }

    /**
     * Handle call end event
     */
    private function handleCallEnd(array $payload, Assistant $assistant): ?CallLog
    {
        $callLog = CallLog::where('call_id', $payload['callId'])->first();

        if (!$callLog) {
            // Create call log if it doesn't exist (in case we missed the start event)
            $callLog = CallLog::create([
                'call_id' => $payload['callId'],
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'reseller_id' => $assistant->user->reseller_id,
                'phone_number' => $payload['phoneNumber'] ?? null,
                'caller_number' => $payload['callerNumber'] ?? null,
                'status' => $this->mapVapiStatus($payload['status'] ?? 'completed'),
                'direction' => $payload['direction'] ?? 'inbound',
                'start_time' => $payload['startTime'] ?? now(),
                'end_time' => now(),
                'duration' => $payload['duration'] ?? null,
                'transcript' => $payload['transcript'] ?? null,
                'summary' => $payload['summary'] ?? null,
                'cost' => $payload['cost'] ?? null,
                'currency' => $payload['currency'] ?? 'USD',
                'metadata' => $payload,
                'webhook_data' => $payload,
            ]);
        } else {
            // Update existing call log
            $callLog->update([
                'status' => $this->mapVapiStatus($payload['status'] ?? 'completed'),
                'end_time' => now(),
                'duration' => $payload['duration'] ?? $callLog->duration,
                'transcript' => $payload['transcript'] ?? $callLog->transcript,
                'summary' => $payload['summary'] ?? $callLog->summary,
                'cost' => $payload['cost'] ?? $callLog->cost,
                'currency' => $payload['currency'] ?? $callLog->currency,
                'webhook_data' => $payload,
            ]);
        }

        Log::info('Call log updated for call end', [
            'call_id' => $callLog->call_id,
            'status' => $callLog->status,
            'duration' => $callLog->duration
        ]);

        return $callLog;
    }

    /**
     * Handle call update event
     */
    private function handleCallUpdate(array $payload, Assistant $assistant): ?CallLog
    {
        $callLog = CallLog::where('call_id', $payload['callId'])->first();

        if ($callLog) {
            $callLog->update([
                'status' => $this->mapVapiStatus($payload['status'] ?? $callLog->status),
                'webhook_data' => $payload,
            ]);

            Log::info('Call log updated for call update', [
                'call_id' => $callLog->call_id,
                'status' => $callLog->status
            ]);
        }

        return $callLog;
    }

    /**
     * Handle end-of-call-report event
     */
    private function handleEndOfCallReport(array $payload, Assistant $assistant): ?CallLog
    {
        $processor = new VapiCallReportProcessor();
        $callLog = $processor->processEndCallReport($payload);

        if ($callLog) {
            // Track reseller usage for billing
            try {
                $usageTracker = new ResellerUsageTracker();
                $usageTracker->trackCallUsage($callLog);
                
                Log::info('End-of-call-report processed with usage tracking', [
                    'call_id' => $callLog->call_id,
                    'assistant_id' => $assistant->id,
                    'reseller_id' => $callLog->reseller_id,
                    'status' => $callLog->status,
                    'duration' => $callLog->duration,
                    'cost' => $callLog->cost,
                ]);
            } catch (\Exception $e) {
                // Don't fail the webhook if usage tracking fails
                Log::error('Error tracking usage in webhook', [
                    'call_id' => $callLog->call_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('Failed to process end-of-call-report', [
                'assistant_id' => $assistant->id,
                'payload_keys' => array_keys($payload)
            ]);
        }

        return $callLog;
    }

    /**
     * Format call log data for API response
     */
    private function formatCallLogResponse(CallLog $callLog): array
    {
        return [
            'id' => $callLog->id,
            'call_id' => $callLog->call_id,
            'assistant_id' => $callLog->assistant_id,
            'user_id' => $callLog->user_id,
            'reseller_id' => $callLog->reseller_id,
            'phone_number' => $callLog->phone_number,
            'caller_number' => $callLog->caller_number,
            'status' => $callLog->status,
            'direction' => $callLog->direction,
            'start_time' => $callLog->start_time?->toISOString(),
            'end_time' => $callLog->end_time?->toISOString(),
            'duration' => $callLog->duration,
            'formatted_duration' => $callLog->formatted_duration,
            // 'transcript' => $callLog->transcript,
            // 'summary' => $callLog->summary,
            'cost' => $callLog->cost,
            'currency' => $callLog->currency,
            'formatted_cost' => $callLog->formatted_cost,
            'call_record_file_name' => $callLog->call_record_file_name,
            'has_recording' => $callLog->has_recording,
            'public_audio_url' => $callLog->public_audio_url,
            'public_audio_player_url' => $callLog->public_audio_player_url,
            // 'metadata' => $callLog->metadata,
            // 'webhook_data' => $callLog->webhook_data,
            'created_at' => $callLog->created_at?->toISOString(),
            'updated_at' => $callLog->updated_at?->toISOString(),
        ];
    }

    /**
     * Map Vapi status to internal status
     */
    private function mapVapiStatus(string $vapiStatus): string
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

        return $statusMap[$vapiStatus] ?? 'initiated';
    }
} 