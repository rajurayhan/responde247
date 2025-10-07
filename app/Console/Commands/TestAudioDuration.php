<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use getID3;

class TestAudioDuration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:audio-duration 
                            {filename : Audio file to test (relative to storage/app/public/recordings/)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test audio duration extraction from audio files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->argument('filename');
        $filePath = 'recordings/' . $filename;

        $this->info("Testing audio duration extraction for: {$filename}");
        $this->info("File path: {$filePath}");

        // Check if file exists
        if (!Storage::disk('public')->exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("File exists, extracting duration...");

        // Test duration extraction
        $duration = $this->extractAudioDuration($filePath);
        
        if ($duration !== null) {
            $this->info("✅ Duration extracted successfully: {$duration} seconds");
            $this->info("Formatted duration: " . $this->formatDuration($duration));
        } else {
            $this->error("❌ Failed to extract duration");
        }

        return 0;
    }

    /**
     * Extract duration from audio file
     */
    private function extractAudioDuration(string $filePath): ?int
    {
        try {
            // Get full path to the file
            $fullPath = Storage::disk('public')->path($filePath);
            
            if (!file_exists($fullPath)) {
                $this->warn("Audio file not found: {$fullPath}");
                return null;
            }

            $this->info("Full file path: {$fullPath}");
            $this->info("File size: " . filesize($fullPath) . " bytes");

            // Method 1: Try using getID3 library
            if (class_exists('getID3')) {
                $this->info("Testing getID3 method...");
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($fullPath);
                
                if (isset($fileInfo['playtime_seconds'])) {
                    $this->info("getID3 found duration: {$fileInfo['playtime_seconds']} seconds");
                    return (int) $fileInfo['playtime_seconds'];
                } else {
                    $this->warn("getID3 could not extract duration");
                }
            } else {
                $this->warn("getID3 library not available");
            }

            // Method 2: Try using FFmpeg if available
            $ffmpegPath = $this->findFFmpeg();
            if ($ffmpegPath) {
                $this->info("Testing FFmpeg method...");
                $command = "{$ffmpegPath} -i " . escapeshellarg($fullPath) . " 2>&1";
                $output = shell_exec($command);
                
                $this->info("FFmpeg output: " . substr($output, 0, 200) . "...");
                
                // Parse duration from FFmpeg output
                if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $output, $matches)) {
                    $hours = (int) $matches[1];
                    $minutes = (int) $matches[2];
                    $seconds = (int) $matches[3];
                    $centiseconds = (int) $matches[4];
                    
                    $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds + ($centiseconds / 100);
                    $this->info("FFmpeg found duration: {$totalSeconds} seconds");
                    return (int) $totalSeconds;
                } else {
                    $this->warn("FFmpeg could not extract duration");
                }
            } else {
                $this->warn("FFmpeg not available");
            }

            // Method 3: Try using soxi (Sound eXchange Info) if available
            $soxiPath = $this->findSoxi();
            if ($soxiPath) {
                $this->info("Testing soxi method...");
                $command = "{$soxiPath} -D " . escapeshellarg($fullPath) . " 2>/dev/null";
                $output = shell_exec($command);
                
                if (is_numeric($output)) {
                    $this->info("soxi found duration: {$output} seconds");
                    return (int) $output;
                } else {
                    $this->warn("soxi could not extract duration");
                }
            } else {
                $this->warn("soxi not available");
            }

            $this->warn("Could not extract duration from audio file: {$filePath}");
            return null;

        } catch (\Exception $e) {
            $this->error("Error extracting audio duration: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find FFmpeg executable
     */
    private function findFFmpeg(): ?string
    {
        $possiblePaths = [
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            '/usr/local/opt/ffmpeg/bin/ffmpeg'
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path) || shell_exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Find soxi executable
     */
    private function findSoxi(): ?string
    {
        $possiblePaths = [
            'soxi',
            '/usr/bin/soxi',
            '/usr/local/bin/soxi',
            '/opt/homebrew/bin/soxi'
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path) || shell_exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Format duration in human readable format
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%02d:%02d', $minutes, $secs);
        }
    }
} 