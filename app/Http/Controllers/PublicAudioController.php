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
                abort(404, 'Audio file not found');
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
} 