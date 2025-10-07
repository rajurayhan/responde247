<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Assistant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VapiCallReportProcessor
{
    /**
     * Process end-of-call-report webhook data
     */
    public function processEndCallReport(array $webhookData): ?CallLog
    {
        try {
            $message = $webhookData['message'] ?? null;
            if (!$message) {
                Log::warning('Missing message in webhook data');
                return null;
            }

            // Extract call information
            $callId = $message['call']['id'] ?? null;
            $assistantId = $message['call']['assistantId'] ?? null;
            $timestamp = $message['timestamp'] ?? null;
            $startedAt = $message['startedAt'] ?? null;
            $endedAt = $message['endedAt'] ?? null;
            $endedReason = $message['endedReason'] ?? null;
            $durationMs = $message['durationMs'] ?? null;
            $durationSeconds = $message['durationSeconds'] ?? null;
            $cost = $message['cost'] ?? null;
            $costBreakdown = $message['costBreakdown'] ?? [];
            $summary = $message['analysis']['summary'] ?? null;
            $successEvaluation = $message['analysis']['successEvaluation'] ?? null;
            $transcript = $message['transcript'] ?? $message['artifact']['transcript'] ?? null;
            $messages = $message['artifact']['messages'] ?? [];
            $recordingUrl = $message['recordingUrl'] ?? $message['artifact']['recordingUrl'] ?? null;
            $stereoRecordingUrl = $message['stereoRecordingUrl'] ?? $message['artifact']['stereoRecordingUrl'] ?? null;

            // Extract phone numbers
            $phoneNumber = $message['phoneNumber']['number'] ?? null;
            $callerNumber = $message['customer']['number'] ?? null;

            // Find the assistant
            $assistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
            if (!$assistant) {
                Log::warning('Assistant not found for vapi_assistant_id', [
                    'assistant_id' => $assistantId,
                    'call_id' => $callId
                ]);
                return null;
            }

            // Check if call was already processed
            $existingCallLog = CallLog::where('call_id', $callId)->first();
            if ($existingCallLog) {
                Log::info('Call already processed, skipping', [
                    'call_id' => $callId,
                    'assistant_id' => $assistantId
                ]);
                return $existingCallLog;
            }

            // Download call recording if available
            $callRecordFileName = null;
            if ($recordingUrl) {
                Log::info('Attempting to download recording', [
                    'call_id' => $callId,
                    'recording_url' => $recordingUrl
                ]);
                $callRecordFileName = $this->downloadCallRecording($recordingUrl, $callId);
                Log::info('Download result', [
                    'call_id' => $callId,
                    'file_name' => $callRecordFileName
                ]);
            } else {
                Log::info('No recording URL available', ['call_id' => $callId]);
            }

            // Determine call status based on ended reason
            $status = $this->mapEndedReasonToStatus($endedReason);

            // Create call log
            $callLog = CallLog::create([
                'call_id' => $callId,
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'reseller_id' => $assistant->user->reseller_id,
                'phone_number' => $phoneNumber,
                'caller_number' => $callerNumber,
                'duration' => $durationSeconds,
                'status' => $status,
                'direction' => 'inbound', // Most Vapi calls are inbound
                'start_time' => $startedAt ? \Carbon\Carbon::parse($startedAt) : null,
                'end_time' => $endedAt ? \Carbon\Carbon::parse($endedAt) : null,
                'transcript' => $transcript,
                'summary' => $summary,
                'cost' => $cost,
                'currency' => 'USD',
                'call_record_file_name' => $callRecordFileName,
                'metadata' => [
                    'ended_reason' => $endedReason,
                    'duration_ms' => $durationMs,
                    'success_evaluation' => $successEvaluation,
                    'recording_url' => $recordingUrl,
                    'stereo_recording_url' => $stereoRecordingUrl,
                    'cost_breakdown' => $costBreakdown,
                    'messages_count' => count($messages),
                    'timestamp' => $timestamp,
                ],
                'webhook_data' => $webhookData,
            ]);

            Log::info('Call log processed from end-of-call-report', [
                'call_id' => $callId,
                'assistant_id' => $assistantId,
                'status' => $status,
                'duration' => $durationSeconds,
                'cost' => $cost,
                'call_record_file_name' => $callRecordFileName
            ]);

            return $callLog;

        } catch (\Exception $e) {
            Log::error('Error processing end-of-call-report webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);
            return null;
        }
    }

    /**
     * Download call recording and store locally
     */
    private function downloadCallRecording(string $recordingUrl, string $callId): ?string
    {
        try {
            // Generate alphanumeric filename
            $fileName = $this->generateAlphanumericFileName();
            $fileExtension = 'wav'; // Vapi recordings are typically WAV
            $fullFileName = $fileName . '.' . $fileExtension;
            
            // Create recordings directory if it doesn't exist
            $directory = 'recordings';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            $filePath = $directory . '/' . $fullFileName;
            
            // Download the file
            $fileContent = file_get_contents($recordingUrl);
            if ($fileContent === false) {
                Log::error('Failed to download recording', [
                    'url' => $recordingUrl,
                    'call_id' => $callId
                ]);
                return null;
            }
            
            // Store the file
            $stored = Storage::disk('public')->put($filePath, $fileContent);
            if (!$stored) {
                Log::error('Failed to store recording', [
                    'file_path' => $filePath,
                    'call_id' => $callId
                ]);
                return null;
            }
            
            Log::info('Call recording downloaded successfully', [
                'file_name' => $fullFileName,
                'file_path' => $filePath,
                'call_id' => $callId,
                'file_size' => strlen($fileContent)
            ]);
            
            return $fullFileName;
            
        } catch (\Exception $e) {
            Log::error('Error downloading call recording', [
                'error' => $e->getMessage(),
                'url' => $recordingUrl,
                'call_id' => $callId
            ]);
            return null;
        }
    }

    /**
     * Generate alphanumeric filename with mix of capital and small letters
     */
    private function generateAlphanumericFileName(): string
    {
        $length = 12; // 12 characters for good uniqueness
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $fileName = '';
        
        for ($i = 0; $i < $length; $i++) {
            $fileName .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $fileName;
    }

    /**
     * Map Vapi ended reason to internal status
     */
    private function mapEndedReasonToStatus(?string $endedReason): string
    {
        if (!$endedReason) {
            return 'completed';
        }

        $statusMap = [
            'customer-ended-call' => 'completed',
            'assistant-ended-call' => 'completed',
            'call-failed' => 'failed',
            'no-answer' => 'no-answer',
            'busy' => 'busy',
            'cancelled' => 'cancelled',
            'timeout' => 'failed',
            'error' => 'failed',
        ];

        return $statusMap[$endedReason] ?? 'completed';
    }

    /**
     * Extract contact information from transcript
     */
    public function extractContactInfo(array $webhookData): array
    {
        $contactInfo = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'company' => null,
            'inquiry_type' => null,
        ];

        try {
            $summary = $webhookData['message']['analysis']['summary'] ?? '';
            $transcript = $webhookData['message']['transcript'] ?? '';
            $messages = $webhookData['message']['artifact']['messages'] ?? [];

            // Extract from summary
            if (preg_match('/([A-Z][a-z]+ [A-Z][a-z]+) called/', $summary, $matches)) {
                $contactInfo['name'] = $matches[1];
            }

            if (preg_match('/phone number \(([^)]+)\)/', $summary, $matches)) {
                $contactInfo['phone'] = $matches[1];
            }

            if (preg_match('/email address \(([^)]+)\)/', $summary, $matches)) {
                $contactInfo['email'] = $matches[1];
            }

            // Extract from transcript
            if (!$contactInfo['name'] && preg_match('/name is ([A-Z][a-z]+ [A-Z][a-z]+)/', $transcript, $matches)) {
                $contactInfo['name'] = $matches[1];
            }

            // Extract inquiry type
            if (preg_match('/interested in ([^.]+)/', $summary, $matches)) {
                $contactInfo['inquiry_type'] = trim($matches[1]);
            }

            // Extract from messages
            foreach ($messages as $message) {
                if ($message['role'] === 'user') {
                    $content = $message['message'] ?? '';
                    
                    // Look for email patterns
                    if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches)) {
                        $contactInfo['email'] = $matches[0];
                    }
                    
                    // Look for phone patterns
                    if (preg_match('/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', $content, $matches)) {
                        $contactInfo['phone'] = $matches[0];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error extracting contact info', [
                'error' => $e->getMessage()
            ]);
        }

        return $contactInfo;
    }

    /**
     * Get call quality metrics
     */
    public function getCallQualityMetrics(array $webhookData): array
    {
        $metrics = [
            'success' => false,
            'duration_seconds' => 0,
            'messages_count' => 0,
            'cost_usd' => 0,
            'has_transcript' => false,
            'has_summary' => false,
            'has_recording' => false,
        ];

        try {
            $message = $webhookData['message'] ?? [];
            
            $metrics['success'] = $message['analysis']['successEvaluation'] ?? false;
            $metrics['duration_seconds'] = $message['durationSeconds'] ?? 0;
            $metrics['messages_count'] = count($message['artifact']['messages'] ?? []);
            $metrics['cost_usd'] = $message['cost'] ?? 0;
            $metrics['has_transcript'] = !empty($message['transcript']);
            $metrics['has_summary'] = !empty($message['analysis']['summary']);
            $metrics['has_recording'] = !empty($message['recordingUrl']);

        } catch (\Exception $e) {
            Log::error('Error getting call quality metrics', [
                'error' => $e->getMessage()
            ]);
        }

        return $metrics;
    }
} 