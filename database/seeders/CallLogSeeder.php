<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CallLog;
use App\Models\Assistant;
use App\Models\User;

class CallLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing assistants and users
        $assistants = Assistant::all();
        $users = User::all();

        if ($assistants->isEmpty() || $users->isEmpty()) {
            $this->command->info('No assistants or users found. Skipping call log seeding.');
            return;
        }

        $statuses = ['initiated', 'ringing', 'in-progress', 'completed', 'failed', 'busy', 'no-answer', 'cancelled'];
        $directions = ['inbound', 'outbound'];
        $phoneNumbers = ['+1234567890', '+1987654321', '+1555123456', '+1555987654'];

        // Create 50 sample call logs
        for ($i = 1; $i <= 50; $i++) {
            $assistant = $assistants->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            $direction = $directions[array_rand($directions)];
            $duration = $status === 'completed' ? rand(30, 600) : ($status === 'failed' ? rand(5, 30) : null);
            
            $startTime = now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $endTime = $status === 'completed' ? $startTime->copy()->addSeconds($duration) : null;

            $transcript = $status === 'completed' ? $this->generateTranscript() : null;
            $summary = $status === 'completed' ? $this->generateSummary() : null;

            CallLog::create([
                'call_id' => 'call_' . uniqid() . '_' . $i,
                'assistant_id' => $assistant->id,
                'user_id' => $user->id,
                'phone_number' => $phoneNumbers[array_rand($phoneNumbers)],
                'caller_number' => '+1' . rand(1000000000, 9999999999),
                'duration' => $duration,
                'status' => $status,
                'direction' => $direction,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'transcript' => $transcript,
                'summary' => $summary,
                'metadata' => [
                    'call_type' => $direction,
                    'assistant_name' => $assistant->name,
                    'user_name' => $user->name,
                ],
                'webhook_data' => [
                    'type' => 'call-end',
                    'callId' => 'call_' . uniqid() . '_' . $i,
                    'assistantId' => $assistant->vapi_assistant_id,
                    'status' => $status,
                    'duration' => $duration,
                ],
                'cost' => $status === 'completed' ? rand(10, 500) / 100 : 0,
                'currency' => 'USD',
            ]);
        }

        $this->command->info('Created 50 sample call logs.');
    }

    private function generateTranscript(): string
    {
        $transcripts = [
            json_encode([
                ['role' => 'user', 'content' => 'Hello, I need help with my order.'],
                ['role' => 'assistant', 'content' => 'Hi there! I\'d be happy to help you with your order. Can you provide your order number?'],
                ['role' => 'user', 'content' => 'Yes, it\'s #12345.'],
                ['role' => 'assistant', 'content' => 'Thank you! I can see your order #12345. It\'s currently being processed and should ship within 2-3 business days.'],
                ['role' => 'user', 'content' => 'That\'s great, thank you!'],
                ['role' => 'assistant', 'content' => 'You\'re welcome! Is there anything else I can help you with today?'],
            ]),
            json_encode([
                ['role' => 'user', 'content' => 'I have a question about your services.'],
                ['role' => 'assistant', 'content' => 'Of course! I\'d be glad to answer any questions about our services. What would you like to know?'],
                ['role' => 'user', 'content' => 'What are your business hours?'],
                ['role' => 'assistant', 'content' => 'We\'re open Monday through Friday, 9 AM to 6 PM EST. We\'re also available 24/7 online for your convenience.'],
                ['role' => 'user', 'content' => 'Perfect, thank you!'],
                ['role' => 'assistant', 'content' => 'You\'re welcome! Have a great day!'],
            ]),
            json_encode([
                ['role' => 'user', 'content' => 'Hi, I need to cancel my subscription.'],
                ['role' => 'assistant', 'content' => 'I understand you want to cancel your subscription. I\'d be happy to help you with that. Can you confirm your account email?'],
                ['role' => 'user', 'content' => 'It\'s john@example.com.'],
                ['role' => 'assistant', 'content' => 'I\'ve found your account. I\'ve processed your cancellation request. You\'ll receive a confirmation email shortly.'],
                ['role' => 'user', 'content' => 'Thank you for your help.'],
                ['role' => 'assistant', 'content' => 'You\'re welcome! We\'re sorry to see you go, but you can always reactivate your subscription anytime.'],
            ])
        ];

        return $transcripts[array_rand($transcripts)];
    }

    private function generateSummary(): string
    {
        $summaries = [
            'Customer inquired about order status and was provided with shipping timeline.',
            'Customer asked about business hours and was given detailed information.',
            'Customer requested subscription cancellation which was processed successfully.',
            'Customer had technical support question that was resolved satisfactorily.',
            'Customer inquired about product availability and pricing information.',
            'Customer requested account information update and was assisted promptly.',
            'Customer had billing question that was clarified and resolved.',
            'Customer inquired about return policy and was provided with detailed instructions.',
        ];

        return $summaries[array_rand($summaries)];
    }
}
