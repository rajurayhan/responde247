<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicAudioPlayerController extends Controller
{
    /**
     * Display public audio player page
     */
    public function showPlayer($fileName)
    {
        try {
            // Validate filename format (alphanumeric with extension)
            if (!preg_match('/^[A-Za-z0-9]{12}\.(wav|mp3)$/', $fileName)) {
                abort(404, 'Invalid file format');
            }

            // Find call log by recording filename
            $callLog = CallLog::where('call_record_file_name', $fileName)->first();
            
            if (!$callLog) {
                abort(404, 'Call recording not found');
            }

            // Check if audio file exists, download if missing
            $filePath = 'recordings/' . $fileName;
            if (!Storage::disk('public')->exists($filePath)) {
                Log::info('Audio file not found, attempting to download', [
                    'file_name' => $fileName,
                    'call_id' => $callLog->call_id
                ]);
                
                $downloaded = $this->downloadMissingFile($fileName, $callLog);
                if (!$downloaded) {
                    Log::warning('Failed to download missing audio file', [
                        'file_name' => $fileName,
                        'call_id' => $callLog->call_id
                    ]);
                    // Continue anyway - user can still see call details
                }
            }

            // Load assistant relationship
            $callLog->load('assistant');

            // Get basic call information for display
            $callInfo = [
                'call_id' => $callLog->call_id,
                'duration' => $callLog->duration,
                'formatted_duration' => $callLog->formatted_duration,
                'status' => $callLog->status,
                'start_time' => $callLog->start_time?->format('M j, Y g:i A'),
                'end_time' => $callLog->end_time?->format('M j, Y g:i A'),
                'phone_number' => $callLog->phone_number,
                'caller_number' => $callLog->caller_number,
                'direction' => $callLog->direction,
                'transcript' => $callLog->transcript,
                'summary' => $callLog->summary,
                'has_recording' => $callLog->hasRecording(),
                'public_audio_url' => $callLog->public_audio_url,
                'file_name' => $fileName,
                // Assistant information
                'assistant' => $callLog->assistant ? [
                    'id' => $callLog->assistant->id,
                    'name' => $callLog->assistant->name,
                    'type' => $callLog->assistant->type,
                    'vapi_assistant_id' => $callLog->assistant->vapi_assistant_id,
                    'phone_number' => $callLog->assistant->phone_number,
                    'created_at' => $callLog->assistant->created_at?->format('M j, Y'),
                ] : null,
            ];

            Log::info('Public audio player accessed', [
                'file_name' => $fileName,
                'call_id' => $callLog->call_id
            ]);

            return view('public.audio-player', compact('callInfo'));

        } catch (\Exception $e) {
            Log::error('Error displaying public audio player', [
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error loading audio player');
        }
    }

    /**
     * Download missing audio file from Vapi
     */
    private function downloadMissingFile(string $fileName, CallLog $callLog): bool
    {
        try {
            // Get recording URL from metadata
            $recordingUrl = $callLog->metadata['recording_url'] ?? null;
            if (!$recordingUrl) {
                Log::warning('No recording URL found in call log metadata', [
                    'call_id' => $callLog->call_id,
                    'file_name' => $fileName
                ]);
                return false;
            }

            Log::info('Attempting to download missing recording', [
                'call_id' => $callLog->call_id,
                'file_name' => $fileName,
                'recording_url' => $recordingUrl
            ]);

            // Create recordings directory if it doesn't exist
            $directory = 'recordings';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            $filePath = $directory . '/' . $fileName;

            // Download the file
            $fileContent = file_get_contents($recordingUrl);
            if ($fileContent === false) {
                Log::error('Failed to download recording from Vapi', [
                    'url' => $recordingUrl,
                    'call_id' => $callLog->call_id,
                    'file_name' => $fileName
                ]);
                return false;
            }

            // Store the file
            $stored = Storage::disk('public')->put($filePath, $fileContent);
            if (!$stored) {
                Log::error('Failed to store downloaded recording', [
                    'file_path' => $filePath,
                    'call_id' => $callLog->call_id,
                    'file_name' => $fileName
                ]);
                return false;
            }

            Log::info('Missing recording downloaded successfully', [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'call_id' => $callLog->call_id,
                'file_size' => strlen($fileContent)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error downloading missing recording', [
                'error' => $e->getMessage(),
                'file_name' => $fileName,
                'call_id' => $callLog->call_id
            ]);
            return false;
        }
    }
}
