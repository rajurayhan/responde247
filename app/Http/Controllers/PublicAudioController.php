<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\CallLog;
use Illuminate\Support\Facades\Log;

class PublicAudioController extends Controller
{
    /**
     * Serve call recording audio file
     */
    public function playAudio($fileName)
    {
        try {
            // Validate filename format (alphanumeric with extension)
            if (!preg_match('/^[A-Za-z0-9]{12}\.(wav|mp3)$/', $fileName)) {
                abort(404, 'Invalid file format');
            }

            // Check if file exists
            $filePath = 'recordings/' . $fileName;
            if (!Storage::disk('public')->exists($filePath)) {
                // File doesn't exist, try to download it
                Log::info('Audio file not found, attempting to download', [
                    'file_name' => $fileName
                ]);
                
                $downloaded = $this->downloadMissingFile($fileName);
                if (!$downloaded) {
                    abort(404, 'Audio file not found and could not be downloaded');
                }
            }

            // Get file info
            $fileSize = Storage::disk('public')->size($filePath);
            $mimeType = $this->getMimeType($fileName);

            // Return audio file with proper headers
            return response()->file(
                Storage::disk('public')->path($filePath),
                [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $fileSize,
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=3600',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error serving audio file', [
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error serving audio file');
        }
    }

    /**
     * Get MIME type based on file extension
     */
    private function getMimeType(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        $mimeTypes = [
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg',
        ];

        return $mimeTypes[$extension] ?? 'audio/wav';
    }

    /**
     * Get call log by recording filename
     */
    public function getCallInfo($fileName)
    {
        try {
            // Validate filename format
            if (!preg_match('/^[A-Za-z0-9]{12}\.(wav|mp3)$/', $fileName)) {
                abort(404, 'Invalid file format');
            }

            // Find call log by recording filename
            $callLog = CallLog::where('call_record_file_name', $fileName)->first();
            
            if (!$callLog) {
                abort(404, 'Call log not found');
            }

            // Return basic call information (no sensitive data)
            return response()->json([
                'success' => true,
                'data' => [
                    'call_id' => $callLog->call_id,
                    'duration' => $callLog->duration,
                    'status' => $callLog->status,
                    'start_time' => $callLog->start_time,
                    'end_time' => $callLog->end_time,
                    'has_recording' => $callLog->hasRecording(),
                    'public_audio_url' => $callLog->public_audio_url,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting call info', [
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error retrieving call information');
        }
    }

    /**
     * Download missing audio file from Vapi
     */
    private function downloadMissingFile(string $fileName): bool
    {
        try {
            // Find call log by recording filename
            $callLog = CallLog::where('call_record_file_name', $fileName)->first();
            
            if (!$callLog) {
                Log::warning('Call log not found for filename', [
                    'file_name' => $fileName
                ]);
                return false;
            }

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
                'file_name' => $fileName
            ]);
            return false;
        }
    }
} 