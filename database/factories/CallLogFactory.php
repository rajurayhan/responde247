<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Assistant;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallLog>
 */
class CallLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['initiated', 'ringing', 'in-progress', 'completed', 'failed', 'busy', 'no-answer', 'cancelled'];
        $directions = ['inbound', 'outbound'];
        $status = $this->faker->randomElement($statuses);
        $direction = $this->faker->randomElement($directions);
        $duration = $status === 'completed' ? $this->faker->numberBetween(30, 600) : ($status === 'failed' ? $this->faker->numberBetween(5, 30) : null);
        
        $startTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $endTime = $status === 'completed' ? $this->faker->dateTimeBetween($startTime, '+1 hour') : null;

        return [
            'call_id' => 'call_' . $this->faker->unique()->uuid,
            'assistant_id' => Assistant::factory(),
            'user_id' => User::factory(),
            'phone_number' => $this->faker->phoneNumber(),
            'caller_number' => $this->faker->phoneNumber(),
            'duration' => $duration,
            'status' => $status,
            'direction' => $direction,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'transcript' => $status === 'completed' ? $this->generateTranscript() : null,
            'summary' => $status === 'completed' ? $this->faker->sentence() : null,
            'metadata' => [
                'call_type' => $direction,
                'assistant_name' => $this->faker->name(),
                'user_name' => $this->faker->name(),
            ],
            'webhook_data' => [
                'type' => 'call-end',
                'callId' => 'call_' . $this->faker->unique()->uuid,
                'assistantId' => 'assistant_' . $this->faker->uuid,
                'status' => $status,
                'duration' => $duration,
            ],
            'cost' => $status === 'completed' ? $this->faker->randomFloat(4, 0.01, 5.00) : 0,
            'currency' => 'USD',
        ];
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
        ];

        return $this->faker->randomElement($transcripts);
    }
}
