<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VapiCallReportProcessor;
use Illuminate\Support\Facades\Storage;

class TestCallRecordingDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:call-recording-download {url : The recording URL to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test call recording download functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recordingUrl = $this->argument('url');
        
        $this->info('Testing call recording download...');
        $this->info('URL: ' . $recordingUrl);
        
        try {
            // Test file_get_contents
            $this->info('Testing file_get_contents...');
            $fileContent = file_get_contents($recordingUrl);
            
            if ($fileContent === false) {
                $this->error('Failed to download file using file_get_contents');
                return 1;
            }
            
            $this->info('✅ File downloaded successfully using file_get_contents');
            $this->info('File size: ' . strlen($fileContent) . ' bytes');
            
            // Test storage
            $this->info('Testing storage...');
            $fileName = 'test_' . time() . '.wav';
            $filePath = 'recordings/' . $fileName;
            
            $stored = Storage::disk('public')->put($filePath, $fileContent);
            
            if (!$stored) {
                $this->error('Failed to store file');
                return 1;
            }
            
            $this->info('✅ File stored successfully');
            $this->info('File path: ' . $filePath);
            $this->info('File exists: ' . (Storage::disk('public')->exists($filePath) ? 'Yes' : 'No'));
            
            // Test public URL
            $publicUrl = url('/p/' . $fileName);
            $this->info('Public URL: ' . $publicUrl);
            
            // Clean up test file
            Storage::disk('public')->delete($filePath);
            $this->info('✅ Test file cleaned up');
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 