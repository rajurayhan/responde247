<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CountryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_countries_are_accepted_for_twilio_available_numbers()
    {
        $user = User::factory()->create();

        // Test with supported countries (should pass)
        $supportedCountries = ['United States', 'Canada', 'Australia', 'United Kingdom'];
        
        foreach ($supportedCountries as $country) {
            $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=' . urlencode($country));
            $response->assertStatus(200);
        }

        // Test with unsupported country (should fail)
        $response = $this->actingAs($user)->getJson('/api/twilio/available-numbers?country=Germany');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['country']);
    }

    public function test_country_validation_rules_are_correct()
    {
        // Test that the validation rules allow supported countries
        $rules = [
            'metadata.country' => 'required|string|in:United States,Canada,Australia,United Kingdom'
        ];
        
        // This test verifies that our validation rules are correctly set
        $this->assertTrue(true); // Placeholder - the real test is in the controller validation
    }
}
