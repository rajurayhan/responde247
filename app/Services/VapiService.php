<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Log\Logger;

class VapiService
{
    protected $baseUrl = 'https://api.vapi.ai';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.vapi.api_key');
        
        if (!$this->apiKey) {
            Log::error('Cloud API key not configured. Please add VAPI_API_KEY to your .env file.');
        }
    }

    /**
     * Get all assistants for a user
     */
    public function getAssistants(User $user = null)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/assistant');

            if ($response->successful()) {
                $assistants = $response->json();
                
                // Filter assistants by user if provided
                // if ($user) {
                //     $assistants = array_filter($assistants, function($assistant) use ($user) {
                //         return isset($assistant['metadata']['user_id']) && 
                //                $assistant['metadata']['user_id'] == $user->id;
                //     });
                // }

                return $assistants;
            }

            Log::error('Cloud API Error: ' . $response->body());
            Log::error('Cloud API Status: ' . $response->status());
            Log::error('Cloud API Headers: ' . json_encode($response->headers()));
            return [];
        } catch (\Exception $e) {
            Log::error('Cloud Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a specific assistant
     */
    public function getAssistant($assistantId)
    {
        try {
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/assistant/' . $assistantId);

            
            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cloud Get Assistant Error: ' . $response->body());
            // Return a fallback structure if Vapi is not available
            return [
                'id' => $assistantId,
                'name' => 'Assistant',
                'status' => 'unknown',
                'model' => ['provider' => 'unknown'],
                'voice' => ['provider' => 'unknown'],
                'metadata' => []
            ];
        } catch (\Exception $e) {
            Log::error('Cloud Get Assistant Service Error: ' . $e->getMessage());
            // Return a fallback structure if Vapi is not available
            return [
                'id' => $assistantId,
                'name' => 'Assistant',
                'status' => 'unknown',
                'model' => ['provider' => 'unknown'],
                'voice' => ['provider' => 'unknown'],
                'metadata' => []
            ];
        }
    }

    /**
     * Create a new assistant
     */
    public function createAssistant(array $data)
    {
        try {
            // Prepare the create data according to Vapi API structure
            $createData = [
                'name' => $data['name'],
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'created_at' => now()->toISOString(),
                    'type' => $data['type'] ?? 'demo',
                ])
            ];

            // Add all optional Vapi API fields if provided
            $this->addOptionalFields($createData, $data);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/assistant', $createData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cloud Create Assistant Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Cloud Create Assistant Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add optional fields to the create data based on Vapi API specification
     */
    private function addOptionalFields(array &$createData, array $data)
    {
        // Transcriber configuration
        if (isset($data['transcriber'])) {
            $createData['transcriber'] = $this->cleanTranscriberConfiguration($data['transcriber']);
        }

        // Model configuration
        if (isset($data['model'])) {
            $createData['model'] = $this->cleanModelConfiguration($data['model']);
        }

        // Voice configuration
        if (isset($data['voice'])) {
            $createData['voice'] = $this->cleanVoiceConfiguration($data['voice']);
        }

        // First message configuration
        if (isset($data['firstMessage'])) {
            $createData['firstMessage'] = $data['firstMessage'];
        }
        if (isset($data['firstMessageInterruptionsEnabled'])) {
            $createData['firstMessageInterruptionsEnabled'] = $data['firstMessageInterruptionsEnabled'];
        }
        if (isset($data['firstMessageMode'])) {
            $createData['firstMessageMode'] = $data['firstMessageMode'];
        }

        // Voicemail detection
        if (isset($data['voicemailDetection'])) {
            $createData['voicemailDetection'] = $data['voicemailDetection'];
        }

        // Client and server messages
        if (isset($data['clientMessages'])) {
            $createData['clientMessages'] = $data['clientMessages'];
        }
        if (isset($data['serverMessages'])) {
            $createData['serverMessages'] = $data['serverMessages'];
        } else {
            // Default server messages
            $createData['serverMessages'] = ['end-of-call-report'];
        }

        // Call configuration
        if (isset($data['maxDurationSeconds'])) {
            $createData['maxDurationSeconds'] = $data['maxDurationSeconds'];
        }
        if (isset($data['backgroundSound'])) {
            $createData['backgroundSound'] = $data['backgroundSound'];
        }
        if (isset($data['modelOutputInMessagesEnabled'])) {
            $createData['modelOutputInMessagesEnabled'] = $data['modelOutputInMessagesEnabled'];
        }

        // Transport configurations
        if (isset($data['transportConfigurations'])) {
            $createData['transportConfigurations'] = $data['transportConfigurations'];
        }

        // Observability plan
        if (isset($data['observabilityPlan'])) {
            $createData['observabilityPlan'] = $data['observabilityPlan'];
        }

        // Credentials
        if (isset($data['credentialIds'])) {
            $createData['credentialIds'] = $data['credentialIds'];
        }
        if (isset($data['credentials'])) {
            $createData['credentials'] = $data['credentials'];
        }

        // Hooks
        if (isset($data['hooks'])) {
            $createData['hooks'] = $data['hooks'];
        }

        // Voicemail and end call messages
        if (isset($data['voicemailMessage'])) {
            $createData['voicemailMessage'] = $data['voicemailMessage'];
        }
        if (isset($data['endCallMessage'])) {
            $createData['endCallMessage'] = $data['endCallMessage'];
        }
        if (isset($data['endCallPhrases'])) {
            $createData['endCallPhrases'] = $data['endCallPhrases'];
        }

        // Compliance plan
        if (isset($data['compliancePlan'])) {
            $createData['compliancePlan'] = $data['compliancePlan'];
        }

        // Background speech denoising plan
        if (isset($data['backgroundSpeechDenoisingPlan'])) {
            $createData['backgroundSpeechDenoisingPlan'] = $data['backgroundSpeechDenoisingPlan'];
        }

        // Analysis plan - Store in metadata for now as Vapi may not accept these directly
        if (isset($data['analysisPlan'])) {
            $createData['metadata']['analysisPlan'] = $data['analysisPlan'];
        }

        // Artifact plan - Store in metadata for now as Vapi may not accept these directly
        if (isset($data['artifactPlan'])) {
            $createData['metadata']['artifactPlan'] = $data['artifactPlan'];
        }

        // Speaking plans
        if (isset($data['startSpeakingPlan'])) {
            $createData['startSpeakingPlan'] = $data['startSpeakingPlan'];
        }
        if (isset($data['stopSpeakingPlan'])) {
            $createData['stopSpeakingPlan'] = $data['stopSpeakingPlan'];
        }

        // Monitor plan
        if (isset($data['monitorPlan'])) {
            $createData['monitorPlan'] = $data['monitorPlan'];
        }

        // Keypad input plan
        if (isset($data['keypadInputPlan'])) {
            $createData['keypadInputPlan'] = $data['keypadInputPlan'];
        }

        // Server configuration for webhook URL
        if (!empty($data['metadata']['webhook_url'])) {
            $createData['server'] = [
                'url' => $data['metadata']['webhook_url']
            ];
        }
    }

    /**
     * Assign a phone number to an assistant
     */
    public function assignPhoneNumber($assistantId, $phoneNumber)
    {
        try {
            Log::info('Cloud Assign Phone Number Request', [
                'assistantId' => $assistantId,
                'phoneNumber' => $phoneNumber
            ]);

            $data = [
                'provider' => 'twilio',
                'number' => $phoneNumber,
                'twilioAccountSid' => config('services.twilio.account_sid'),
                'twilioAuthToken' => config('services.twilio.auth_token'),
                'assistantId' => $assistantId,
                'name' => 'Twilio Number'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/phone-number', $data);

            if ($response->successful()) {
                Log::info('Cloud Assign Phone Number Success', [
                    'assistantId' => $assistantId,
                    'phoneNumber' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            Log::error('Cloud Assign Phone Number Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Cloud Assign Phone Number Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update an assistant
     */
    public function updateAssistant($assistantId, array $data)
    {
        try {
            // First, get the current assistant data from Vapi to preserve existing values
            $currentAssistant = $this->getAssistant($assistantId);
            $data['metadata']['user_email'] = User::find($data['user_id'])->email ?? null;
            
            // Build update data with only the fields that Vapi accepts for updates
            $updateData = [
                'name' => $data['name'] ?? $currentAssistant['name'],
                'metadata' => array_merge($currentAssistant['metadata'] ?? [], $data['metadata'] ?? [])
            ];
            
            // Update the updated_at timestamp
            $updateData['metadata']['updated_at'] = now()->toISOString();
            
            // Handle type field - ensure it's properly synced with metadata
            if (isset($data['type'])) {
                $updateData['metadata']['type'] = $data['type'];
            } elseif (isset($currentAssistant['metadata']['type'])) {
                // Preserve existing type if not provided in update
                $updateData['metadata']['type'] = $currentAssistant['metadata']['type'];
            } else {
                // Default to demo if no type is set
                $updateData['metadata']['type'] = 'demo';
            }
            
            // Add all optional Vapi API fields if provided, merging with existing data
            $this->addOptionalFieldsForUpdate($updateData, $data, $currentAssistant);
            Log::info('Cloud Update Assistant Request', $updateData);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->put($this->baseUrl . '/assistant/' . $assistantId, $updateData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cloud Update Assistant Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'assistant_id' => $assistantId,
                'request_data' => $updateData
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Cloud Update Assistant Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add optional fields to the update data, merging with existing values
     */
    private function addOptionalFieldsForUpdate(array &$updateData, array $data, array $currentAssistant)
    {
        // Transcriber configuration
        if (isset($data['transcriber'])) {
            $updateData['transcriber'] = $this->cleanTranscriberConfiguration($data['transcriber']);
        } elseif (isset($currentAssistant['transcriber'])) {
            $updateData['transcriber'] = $this->cleanTranscriberConfiguration($currentAssistant['transcriber']);
        }

        // Model configuration
        if (isset($data['model'])) {
            $updateData['model'] = $this->cleanModelConfiguration($this->mergeModelData($currentAssistant['model'] ?? [], $data['model']));
        } elseif (isset($currentAssistant['model'])) {
            $updateData['model'] = $this->cleanModelConfiguration($currentAssistant['model']);
        }
        Log::info('Step....4');
        // Voice configuration
        if (isset($data['voice'])) {
            Log::info('Cloud Update Assistant Voice Configuration', [
                'newData' => $data['voice'], 
                'oldData' =>   $currentAssistant['voice'] ?? [],
                'mergeData' => $this->cleanVoiceConfiguration($this->mergeVoiceData($currentAssistant['voice'] ?? [], $data['voice']))
            ]);
            $updateData['voice'] = $this->cleanVoiceConfiguration($this->mergeVoiceData($currentAssistant['voice'] ?? [], $data['voice']));
            //dd([$data['voice'], $this->mergeVoiceData($data['voice'], $currentAssistant['voice'] ?? []), $updateData['voice']]);
        } elseif (isset($currentAssistant['voice'])) {
            Log::error('Should not be here..');
            $updateData['voice'] = $this->cleanVoiceConfiguration($currentAssistant['voice']);
        }

        // First message configuration
        if (isset($data['firstMessage'])) {
            $updateData['firstMessage'] = $data['firstMessage'];
        } elseif (isset($currentAssistant['firstMessage'])) {
            $updateData['firstMessage'] = $currentAssistant['firstMessage'];
        }
        if (isset($data['firstMessageInterruptionsEnabled'])) {
            $updateData['firstMessageInterruptionsEnabled'] = $data['firstMessageInterruptionsEnabled'];
        } elseif (isset($currentAssistant['firstMessageInterruptionsEnabled'])) {
            $updateData['firstMessageInterruptionsEnabled'] = $currentAssistant['firstMessageInterruptionsEnabled'];
        }
        if (isset($data['firstMessageMode'])) {
            $updateData['firstMessageMode'] = $data['firstMessageMode'];
        } elseif (isset($currentAssistant['firstMessageMode'])) {
            $updateData['firstMessageMode'] = $currentAssistant['firstMessageMode'];
        }

        // Voicemail detection
        if (isset($data['voicemailDetection'])) {
            $updateData['voicemailDetection'] = $data['voicemailDetection'];
        } elseif (isset($currentAssistant['voicemailDetection'])) {
            $updateData['voicemailDetection'] = $currentAssistant['voicemailDetection'];
        }

        // Client and server messages
        if (isset($data['clientMessages'])) {
            $updateData['clientMessages'] = $data['clientMessages'];
        } elseif (isset($currentAssistant['clientMessages'])) {
            $updateData['clientMessages'] = $currentAssistant['clientMessages'];
        }
        if (isset($data['serverMessages'])) {
            $updateData['serverMessages'] = $data['serverMessages'];
        } elseif (isset($currentAssistant['serverMessages'])) {
            $updateData['serverMessages'] = $currentAssistant['serverMessages'];
        } else {
            // Default server messages
            $updateData['serverMessages'] = ['end-of-call-report'];
        }

        // Call configuration
        if (isset($data['maxDurationSeconds'])) {
            $updateData['maxDurationSeconds'] = $data['maxDurationSeconds'];
        } elseif (isset($currentAssistant['maxDurationSeconds'])) {
            $updateData['maxDurationSeconds'] = $currentAssistant['maxDurationSeconds'];
        }
        if (isset($data['backgroundSound'])) {
            $updateData['backgroundSound'] = $data['backgroundSound'];
        } elseif (isset($currentAssistant['backgroundSound'])) {
            $updateData['backgroundSound'] = $currentAssistant['backgroundSound'];
        }
        if (isset($data['modelOutputInMessagesEnabled'])) {
            $updateData['modelOutputInMessagesEnabled'] = $data['modelOutputInMessagesEnabled'];
        } elseif (isset($currentAssistant['modelOutputInMessagesEnabled'])) {
            $updateData['modelOutputInMessagesEnabled'] = $currentAssistant['modelOutputInMessagesEnabled'];
        }

        // Transport configurations
        if (isset($data['transportConfigurations'])) {
            $updateData['transportConfigurations'] = $data['transportConfigurations'];
        } elseif (isset($currentAssistant['transportConfigurations'])) {
            $updateData['transportConfigurations'] = $currentAssistant['transportConfigurations'];
        }

        // Observability plan
        if (isset($data['observabilityPlan'])) {
            $updateData['observabilityPlan'] = $data['observabilityPlan'];
        } elseif (isset($currentAssistant['observabilityPlan'])) {
            $updateData['observabilityPlan'] = $currentAssistant['observabilityPlan'];
        }

        // Credentials
        if (isset($data['credentialIds'])) {
            $updateData['credentialIds'] = $data['credentialIds'];
        } elseif (isset($currentAssistant['credentialIds'])) {
            $updateData['credentialIds'] = $currentAssistant['credentialIds'];
        }
        if (isset($data['credentials'])) {
            $updateData['credentials'] = $data['credentials'];
        } elseif (isset($currentAssistant['credentials'])) {
            $updateData['credentials'] = $currentAssistant['credentials'];
        }

        // Hooks
        if (isset($data['hooks'])) {
            $updateData['hooks'] = $data['hooks'];
        } elseif (isset($currentAssistant['hooks'])) {
            $updateData['hooks'] = $currentAssistant['hooks'];
        }

        // Voicemail and end call messages
        if (isset($data['voicemailMessage'])) {
            $updateData['voicemailMessage'] = $data['voicemailMessage'];
        } elseif (isset($currentAssistant['voicemailMessage'])) {
            $updateData['voicemailMessage'] = $currentAssistant['voicemailMessage'];
        }
        if (isset($data['endCallMessage'])) {
            $updateData['endCallMessage'] = $data['endCallMessage'];
        } elseif (isset($currentAssistant['endCallMessage'])) {
            $updateData['endCallMessage'] = $currentAssistant['endCallMessage'];
        }
        if (isset($data['endCallPhrases'])) {
            $updateData['endCallPhrases'] = $data['endCallPhrases'];
        } elseif (isset($currentAssistant['endCallPhrases'])) {
            $updateData['endCallPhrases'] = $currentAssistant['endCallPhrases'];
        }

        // Compliance plan
        if (isset($data['compliancePlan'])) {
            $updateData['compliancePlan'] = $data['compliancePlan'];
        } elseif (isset($currentAssistant['compliancePlan'])) {
            $updateData['compliancePlan'] = $currentAssistant['compliancePlan'];
        }

        // Background speech denoising plan
        if (isset($data['backgroundSpeechDenoisingPlan'])) {
            $updateData['backgroundSpeechDenoisingPlan'] = $data['backgroundSpeechDenoisingPlan'];
        } elseif (isset($currentAssistant['backgroundSpeechDenoisingPlan'])) {
            $updateData['backgroundSpeechDenoisingPlan'] = $currentAssistant['backgroundSpeechDenoisingPlan'];
        }

        // Analysis plan - Store in metadata for now as Vapi may not accept these directly
        if (isset($data['analysisPlan'])) {
            $updateData['metadata']['analysisPlan'] = $data['analysisPlan'];
        } elseif (isset($currentAssistant['metadata']['analysisPlan'])) {
            $updateData['metadata']['analysisPlan'] = $currentAssistant['metadata']['analysisPlan'];
        } elseif (isset($currentAssistant['analysisPlan'])) {
            $updateData['metadata']['analysisPlan'] = $currentAssistant['analysisPlan'];
        }

        // Artifact plan - Store in metadata for now as Vapi may not accept these directly
        if (isset($data['artifactPlan'])) {
            $updateData['metadata']['artifactPlan'] = $data['artifactPlan'];
        } elseif (isset($currentAssistant['metadata']['artifactPlan'])) {
            $updateData['metadata']['artifactPlan'] = $currentAssistant['metadata']['artifactPlan'];
        } elseif (isset($currentAssistant['artifactPlan'])) {
            $updateData['metadata']['artifactPlan'] = $currentAssistant['artifactPlan'];
        }

        // Speaking plans
        if (isset($data['startSpeakingPlan'])) {
            $updateData['startSpeakingPlan'] = $data['startSpeakingPlan'];
        } elseif (isset($currentAssistant['startSpeakingPlan'])) {
            $updateData['startSpeakingPlan'] = $currentAssistant['startSpeakingPlan'];
        }
        if (isset($data['stopSpeakingPlan'])) {
            $updateData['stopSpeakingPlan'] = $data['stopSpeakingPlan'];
        } elseif (isset($currentAssistant['stopSpeakingPlan'])) {
            $updateData['stopSpeakingPlan'] = $currentAssistant['stopSpeakingPlan'];
        }

        // Monitor plan
        if (isset($data['monitorPlan'])) {
            $updateData['monitorPlan'] = $data['monitorPlan'];
        } elseif (isset($currentAssistant['monitorPlan'])) {
            $updateData['monitorPlan'] = $currentAssistant['monitorPlan'];
        }

        // Keypad input plan
        if (isset($data['keypadInputPlan'])) {
            $updateData['keypadInputPlan'] = $data['keypadInputPlan'];
        } elseif (isset($currentAssistant['keypadInputPlan'])) {
            $updateData['keypadInputPlan'] = $currentAssistant['keypadInputPlan'];
        }

        // Handle webhook URL specifically - only add server if webhook URL is provided
        if (isset($data['metadata']['webhook_url'])) {
            if (!empty($data['metadata']['webhook_url'])) {
                $updateData['server'] = [
                    'url' => $data['metadata']['webhook_url']
                ];
            } else {
                // Remove server configuration if webhook URL is empty
                $updateData['server'] = null;
            }
        } elseif (isset($currentAssistant['server'])) {
            // Preserve existing server configuration if not provided in update
            $updateData['server'] = $currentAssistant['server'];
        }
    }

    /**
     * Merge model data preserving existing configuration
     */
    private function mergeModelData($currentModel, $newModel)
    {
        // Start with current model data
        $mergedModel = $currentModel;
        
        // Update with new model data, preserving existing fields not in the request
        if (isset($newModel['provider'])) {
            $mergedModel['provider'] = $newModel['provider'];
        }
        
        if (isset($newModel['model'])) {
            $mergedModel['model'] = $newModel['model'];
        }
        
        // Handle messages array - preserve existing messages structure
        if (isset($newModel['messages'])) {
            $mergedModel['messages'] = $newModel['messages'];
        }
        
        // Preserve all other model configuration (maxTokens, temperature, etc.)
        // that might be set in Vapi but not in our UI
        foreach ($currentModel as $key => $value) {
            if (!isset($newModel[$key]) && $key !== 'messages') {
                $mergedModel[$key] = $value;
            }
        }
        
        return $mergedModel;
    }

    /**
     * Merge voice data preserving existing configuration
     */
    private function mergeVoiceData($currentVoice, $newVoice)
    {
        // Start with current voice data
        $mergedVoice = $currentVoice;
        
        // Update with new voice data, preserving existing fields not in the request
        if (isset($newVoice['provider'])) {
            $mergedVoice['provider'] = $newVoice['provider'];
        }
        
        if (isset($newVoice['model'])) {
            $mergedVoice['model'] = $newVoice['model'];
        }
        
        if (isset($newVoice['voiceId'])) {
            $mergedVoice['voiceId'] = $newVoice['voiceId'];
        }
        
        // Preserve all other voice configuration (maxTokens, temperature, etc.)
        // that might be set in Vapi but not in our UI
        foreach ($currentVoice as $key => $value) {
            if (!isset($newVoice[$key])) {
                $mergedVoice[$key] = $value;
            }
        }
        
        return $mergedVoice;
    }

    /**
     * Delete an assistant
     */
    public function deleteAssistant($assistantId)
    {
        try {
            Log::info('Attempting to delete assistant from Vapi', ['assistant_id' => $assistantId]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl . '/assistant/' . $assistantId);

            Log::info('Cloud delete response', [
                'assistant_id' => $assistantId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('Successfully deleted assistant from Vapi', ['assistant_id' => $assistantId]);
                return true;
            }

            Log::error('Cloud Delete Assistant Error', [
                'assistant_id' => $assistantId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Cloud Delete Assistant Service Error', [
                'assistant_id' => $assistantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get assistant statistics
     */
    public function getAssistantStats($assistantId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/assistant/' . $assistantId . '/stats');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cloud Get Assistant Stats Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Cloud Get Assistant Stats Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean model configuration to remove invalid properties based on provider
     */
    private function cleanModelConfiguration(array $modelConfig)
    {
        $provider = $modelConfig['provider'] ?? 'openai';
        $cleaned = $modelConfig;

        // Remove invalid properties based on provider
        switch ($provider) {
            case 'openai':
                // OpenAI supports: model, messages, tools, maxTokens, functionCall, functionCallBehavior
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'functionCall', 'functionCallBehavior'];
                break;
            case 'anthropic':
                // Anthropic supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'google':
                // Google supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'azure':
                // Azure supports: model, messages, tools, maxTokens, temperature, topP, frequencyPenalty, presencePenalty, stop, functionCall, functionCallBehavior, apiVersion
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'frequencyPenalty', 'presencePenalty', 'stop', 'functionCall', 'functionCallBehavior', 'apiVersion'];
                break;
            case 'cohere':
                // Cohere supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'mistral':
                // Mistral supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'groq':
                // Groq supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'deepgram':
                // Deepgram supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'perplexity':
                // Perplexity supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'openrouter':
                // OpenRouter supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'together':
                // Together AI supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'replicate':
                // Replicate supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'huggingface':
                // Hugging Face supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            case 'custom':
                // Custom supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'topK'];
                break;
            default:
                // Default to OpenAI properties
                $allowed = ['provider', 'model', 'messages', 'tools', 'maxTokens', 'temperature', 'topP', 'frequencyPenalty', 'presencePenalty', 'stop', 'functionCall', 'functionCallBehavior'];
        }

        // Remove properties not in the allowed list
        $cleaned = array_intersect_key($cleaned, array_flip($allowed));

        return $cleaned;
    }

    /**
     * Clean voice configuration to remove invalid properties based on provider
     */
    private function cleanVoiceConfiguration(array $voiceConfig)
    {
        $provider = $voiceConfig['provider'] ?? 'Cloud';
        $cleaned = $voiceConfig;

        // Remove invalid properties based on provider
        switch ($provider) {
            case 'Cloud':
                // Vapi (Sulus) supports: provider, voiceId, speed
                $allowed = ['provider', 'voiceId', 'speed'];
                break;
            case '11labs':
                // ElevenLabs supports: provider, voiceId, model, speed
                $allowed = ['provider', 'voiceId', 'model', 'speed'];
                break;
            case 'azure':
                // Azure supports: provider, voiceId
                $allowed = ['provider', 'voiceId'];
                break;
            case 'google':
                // Google supports: provider, voiceId
                $allowed = ['provider', 'voiceId'];
                break;
            case 'aws':
                // AWS Polly supports: provider, voiceId
                $allowed = ['provider', 'voiceId'];
                break;
            case 'openai':
                // OpenAI TTS supports: provider, voiceId, model, speed
                $allowed = ['provider', 'voiceId', 'model', 'speed'];
                break;
            case 'playht':
                // PlayHT supports: provider, voiceId, model, speed
                $allowed = ['provider', 'voiceId', 'model', 'speed'];
                break;
            case 'deepgram':
                // Deepgram supports: provider, voiceId, speed
                $allowed = ['provider', 'voiceId', 'speed'];
                break;
            case 'rime':
                // Rime supports: provider, voiceId, speed
                $allowed = ['provider', 'voiceId', 'speed'];
                break;
            case 'custom':
                // Custom supports: provider, voiceId
                $allowed = ['provider', 'voiceId'];
                break;
            default:
                // Default to Vapi properties
                $allowed = ['provider', 'voiceId', 'speed'];
        }

        // Remove properties not in the allowed list
        $cleaned = array_intersect_key($cleaned, array_flip($allowed));

        return $cleaned;
    }

    /**
     * Clean transcriber configuration to remove invalid properties based on provider
     */
    private function cleanTranscriberConfiguration(array $transcriberConfig)
    {
        $provider = $transcriberConfig['provider'] ?? 'deepgram';
        $cleaned = $transcriberConfig;

        // Only allow essential fields for all transcriber providers
        switch ($provider) {
            case 'deepgram':
                // Deepgram supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'language','model','confidenceThreshold'];
                break;
            case '11labs':
                // Perplexity supports: model, messages, tools, maxTokens, temperature, topP, topK
                $allowed = ['provider', 'language', 'model',];
                break;
            default:
                // Default to OpenAI properties
                $allowed = ['provider', 'language', 'confidenceThreshold', 'formatTurns'];
        }

        // Remove properties not in the allowed list
        $cleaned = array_intersect_key($cleaned, array_flip($allowed));

        return $cleaned;
    }
} 