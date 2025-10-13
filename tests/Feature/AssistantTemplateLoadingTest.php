<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\Reseller;
use App\Http\Controllers\SettingController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssistantTemplateLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected $reseller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a reseller for the test manually
        $this->reseller = Reseller::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_name' => 'Test Reseller',
            'domain' => 'test.example.com',
            'company_email' => 'test@example.com',
            'status' => 'active'
        ]);
    }

    public function test_can_load_assistant_templates()
    {
        // Create templates for the reseller
        Setting::create([
            'reseller_id' => $this->reseller->id,
            'key' => 'assistant_system_prompt_template',
            'value' => 'You are a helpful assistant for {{company_name}} in the {{company_industry}} industry.',
            'type' => 'string'
        ]);

        Setting::create([
            'reseller_id' => $this->reseller->id,
            'key' => 'assistant_first_message_template',
            'value' => 'Hello! Welcome to {{company_name}}. How can I help you today?',
            'type' => 'string'
        ]);

        Setting::create([
            'reseller_id' => $this->reseller->id,
            'key' => 'assistant_end_call_message_template',
            'value' => 'Thank you for calling {{company_name}}. Have a great day!',
            'type' => 'string'
        ]);

        // Set the current reseller context
        app()->instance('currentReseller', $this->reseller);

        // Test the controller method directly
        $controller = new SettingController();
        $response = $controller->getAssistantTemplates();

        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals('You are a helpful assistant for {{company_name}} in the {{company_industry}} industry.', $responseData['data']['system_prompt']);
        $this->assertEquals('Hello! Welcome to {{company_name}}. How can I help you today?', $responseData['data']['first_message']);
        $this->assertEquals('Thank you for calling {{company_name}}. Have a great day!', $responseData['data']['end_call_message']);
    }

    public function test_returns_empty_templates_when_none_exist()
    {
        // Set the current reseller context
        app()->instance('currentReseller', $this->reseller);

        // Test the controller method directly
        $controller = new SettingController();
        $response = $controller->getAssistantTemplates();

        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals('', $responseData['data']['system_prompt']);
        $this->assertEquals('', $responseData['data']['first_message']);
        $this->assertEquals('', $responseData['data']['end_call_message']);
    }

    public function test_returns_error_when_no_reseller_context()
    {
        // Remove reseller context
        app()->forgetInstance('currentReseller');

        // Test the controller method directly
        $controller = new SettingController();
        $response = $controller->getAssistantTemplates();

        $responseData = $response->getData(true);
        
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Reseller context not found', $responseData['message']);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_templates_are_reseller_specific()
    {
        // Create another reseller manually
        $otherReseller = Reseller::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_name' => 'Other Reseller',
            'domain' => 'other.example.com',
            'company_email' => 'other@example.com',
            'status' => 'active'
        ]);

        // Create templates for the other reseller
        Setting::create([
            'reseller_id' => $otherReseller->id,
            'key' => 'assistant_system_prompt_template',
            'value' => 'You are a different assistant for {{company_name}}.',
            'type' => 'string'
        ]);

        // Create templates for current reseller
        Setting::create([
            'reseller_id' => $this->reseller->id,
            'key' => 'assistant_system_prompt_template',
            'value' => 'You are a helpful assistant for {{company_name}} in the {{company_industry}} industry.',
            'type' => 'string'
        ]);

        // Set the current reseller context
        app()->instance('currentReseller', $this->reseller);

        // Test the controller method directly
        $controller = new SettingController();
        $response = $controller->getAssistantTemplates();

        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals('You are a helpful assistant for {{company_name}} in the {{company_industry}} industry.', $responseData['data']['system_prompt']);
        $this->assertEquals('', $responseData['data']['first_message']);
        $this->assertEquals('', $responseData['data']['end_call_message']);

        // Verify we got the correct reseller's template, not the other one
        $this->assertNotEquals('You are a different assistant for {{company_name}}.', $responseData['data']['system_prompt']);
    }
}