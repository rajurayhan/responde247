<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VapiCallReportProcessor;
use App\Models\CallLog;
use Illuminate\Support\Facades\Log;

class ProcessVapiWebhookSample extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapi:process-sample {file : Path to the sample webhook JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a sample Vapi webhook file and create call logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        try {
            // Read and decode the JSON file
            $jsonContent = file_get_contents($filePath);
            $webhookData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON file: ' . json_last_error_msg());
                return 1;
            }

            $this->info('Processing webhook data...');
            
            // Process the webhook data
            $processor = new VapiCallReportProcessor();
            $callLog = $processor->processEndCallReport($webhookData);

            if ($callLog) {
                $this->info('✅ Call log created successfully!');
                                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Call ID', $callLog->call_id],
                            ['Assistant ID', $callLog->assistant_id],
                            ['User ID', $callLog->user_id],
                            ['Status', $callLog->status],
                            ['Duration', $callLog->duration . ' seconds'],
                            ['Cost', $callLog->cost . ' ' . $callLog->currency],
                            ['Phone Number', $callLog->phone_number],
                            ['Caller Number', $callLog->caller_number],
                            ['Has Transcript', $callLog->transcript ? 'Yes' : 'No'],
                            ['Has Summary', $callLog->summary ? 'Yes' : 'No'],
                            ['Has Recording', $callLog->hasRecording() ? 'Yes' : 'No'],
                            ['Recording File', $callLog->call_record_file_name ?: 'N/A'],
                            ['Public Audio URL', $callLog->public_audio_url ?: 'N/A'],
                        ]
                    );

                // Extract contact information
                $contactInfo = $processor->extractContactInfo($webhookData);
                if (array_filter($contactInfo)) {
                    $this->info('📞 Contact Information Extracted:');
                    $contactRows = [];
                    foreach (array_filter($contactInfo) as $field => $value) {
                        $contactRows[] = [$field, $value];
                    }
                    if (empty($contactRows)) {
                        $contactRows = [['No contact info found', '']];
                    }
                    $this->table(['Field', 'Value'], $contactRows);
                }

                // Get call quality metrics
                $metrics = $processor->getCallQualityMetrics($webhookData);
                $this->info('📊 Call Quality Metrics:');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Success', $metrics['success'] ? 'Yes' : 'No'],
                        ['Duration (seconds)', $metrics['duration_seconds']],
                        ['Messages Count', $metrics['messages_count']],
                        ['Cost (USD)', $metrics['cost_usd']],
                        ['Has Transcript', $metrics['has_transcript'] ? 'Yes' : 'No'],
                        ['Has Summary', $metrics['has_summary'] ? 'Yes' : 'No'],
                        ['Has Recording', $metrics['has_recording'] ? 'Yes' : 'No'],
                    ]
                );

            } else {
                $this->error('❌ Failed to create call log');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Error processing webhook: ' . $e->getMessage());
            Log::error('Error in ProcessVapiWebhookSample command', [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            return 1;
        }

        return 0;
    }
} 