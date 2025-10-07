<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VapiAssistantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic fields
            'name' => 'required|string|max:40',
            'type' => 'nullable|string|in:demo,production',
            'user_id' => 'nullable|integer|exists:users,id',
            'selected_phone_number' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/',

            // Transcriber configuration
            'transcriber' => 'nullable|array',
            'transcriber.provider' => 'nullable|string|in:11labs,deepgram',
            'transcriber.language' => 'nullable|string|size:2',
            'transcriber.confidenceThreshold' => 'nullable|numeric|min:0|max:1',
            'transcriber.formatTurns' => 'nullable|boolean',

            // Model configuration
            'model' => 'nullable|array',
            'model.provider' => 'nullable|string|in:openai,anthropic,google,azure,cohere,replicate,perplexity,deepseek,gemini',
            'model.model' => 'nullable|string',
            'model.messages' => 'nullable|array',
            'model.tools' => 'nullable|array',
            'model.maxTokens' => 'nullable|integer|min:1',
            'model.functionCall' => 'nullable|string|in:none,auto',
            'model.functionCallBehavior' => 'nullable|string|in:auto,required',

            // Voice configuration
            'voice' => 'nullable|array',
            'voice.provider' => 'nullable|string|in:vapi,11labs,azure,openai,playht,deepgram,aws,google,lyra,rime,coqui,polly,azure-neural,azure-neural-multilingual,custom',
            'voice.voiceId' => 'nullable|string',
            'voice.speed' => 'nullable|numeric|min:0.25|max:4',
            'voice.model' => 'nullable|string',

            // First message configuration
            'firstMessage' => 'nullable|string|max:1000',
            'firstMessageInterruptionsEnabled' => 'nullable|boolean',
            'firstMessageMode' => 'nullable|string|in:assistant-speaks-first,assistant-speaks-first-with-model-generated-message,assistant-waits-for-user',

            // Voicemail detection
            'voicemailDetection' => 'nullable|array',
            'voicemailDetection.enabled' => 'nullable|boolean',
            'voicemailDetection.beepDetection' => 'nullable|boolean',
            'voicemailDetection.silenceDuration' => 'nullable|integer|min:0',
            'voicemailDetection.greetingDuration' => 'nullable|integer|min:0',

            // Messages configuration
            'clientMessages' => 'nullable|array',
            'serverMessages' => 'nullable|array',

            // Call configuration
            'maxDurationSeconds' => 'nullable|integer|min:10|max:43200',
            'backgroundSound' => 'nullable|string|in:office,coffee-shop,convention-hall,summit,traffic,rain,storm,wind,fire,crackling-fire,rain-on-roof,coastal-storm,howling-wind,seagulls,night-insects,fan,white-noise,off',
            'modelOutputInMessagesEnabled' => 'nullable|boolean',

            // Transport configurations
            'transportConfigurations' => 'nullable|array',

            // Observability plan
            'observabilityPlan' => 'nullable|array',
            'observabilityPlan.langfuse' => 'nullable|array',
            'observabilityPlan.langfuse.publicKey' => 'nullable|string',
            'observabilityPlan.langfuse.secretKey' => 'nullable|string',
            'observabilityPlan.langfuse.host' => 'nullable|url',

            // Credentials
            'credentialIds' => 'nullable|array',
            'credentials' => 'nullable|array',

            // Hooks
            'hooks' => 'nullable|array',

            // Voicemail and end call messages
            'voicemailMessage' => 'nullable|string|max:1000',
            'endCallMessage' => 'nullable|string|max:1000',
            'endCallPhrases' => 'nullable|array',

            // Compliance plan
            'compliancePlan' => 'nullable|array',
            'compliancePlan.recordingEnabled' => 'nullable|boolean',
            'compliancePlan.transcriptionEnabled' => 'nullable|boolean',
            'compliancePlan.dataRetentionDays' => 'nullable|integer|min:1',

            // Background speech denoising plan
            'backgroundSpeechDenoisingPlan' => 'nullable|array',
            'backgroundSpeechDenoisingPlan.smartDenoising' => 'nullable|boolean',
            'backgroundSpeechDenoisingPlan.fourierDenoising' => 'nullable|boolean',

            // Note: analysisPlan and artifactPlan are not supported by Vapi API
            // These are handled internally by Vapi and returned in webhooks

            // Speaking plans
            'startSpeakingPlan' => 'nullable|array',
            'startSpeakingPlan.initialDelayMs' => 'nullable|integer|min:0',
            'startSpeakingPlan.backgroundSound' => 'nullable|string',
            'startSpeakingPlan.backgroundSoundVolume' => 'nullable|numeric|min:0|max:1',

            'stopSpeakingPlan' => 'nullable|array',
            'stopSpeakingPlan.initialDelayMs' => 'nullable|integer|min:0',
            'stopSpeakingPlan.backgroundSound' => 'nullable|string',
            'stopSpeakingPlan.backgroundSoundVolume' => 'nullable|numeric|min:0|max:1',

            // Monitor plan
            'monitorPlan' => 'nullable|array',
            'monitorPlan.listenEnabled' => 'nullable|boolean',
            'monitorPlan.controlEnabled' => 'nullable|boolean',
            'monitorPlan.websocketUrl' => 'nullable|url',

            // Keypad input plan
            'keypadInputPlan' => 'nullable|array',
            'keypadInputPlan.enabled' => 'nullable|boolean',
            'keypadInputPlan.maxDigits' => 'nullable|integer|min:1|max:10',
            'keypadInputPlan.terminateOnHash' => 'nullable|boolean',

            // Metadata
            'metadata' => 'nullable|array',
            'metadata.company_name' => 'nullable|string|max:255',
            'metadata.industry' => 'nullable|string|max:255',
            'metadata.country' => 'required|string|in:United States,Canada,Australia,United Kingdom',
            'metadata.services_products' => 'nullable|string|max:1000',
            'metadata.sms_phone_number' => 'nullable|string|max:20',
            'metadata.assistant_phone_number' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'metadata.webhook_url' => 'nullable|url|max:500',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Assistant name is required.',
            'name.max' => 'Assistant name cannot exceed 40 characters.',
            'type.in' => 'Type must be either demo or production.',
            'user_id.exists' => 'The selected user does not exist.',
            'selected_phone_number.regex' => 'Phone number must be in international format (e.g., +1234567890).',
            'transcriber.provider.in' => 'Invalid transcriber provider.',
            'transcriber.language.size' => 'Language code must be exactly 2 characters.',
            'transcriber.confidenceThreshold.between' => 'Confidence threshold must be between 0 and 1.',
            'model.provider.in' => 'Invalid model provider.',
            'model.temperature.between' => 'Temperature must be between 0 and 2.',
            'model.topP.between' => 'Top P must be between 0 and 1.',
            'voice.provider.in' => 'Invalid voice provider.',
            'voice.speed.between' => 'Speed must be between 0.25 and 4.',
            'voice.pitch.between' => 'Pitch must be between 0.5 and 2.',
            'firstMessageMode.in' => 'Invalid first message mode.',
            'maxDurationSeconds.between' => 'Max duration must be between 10 and 43200 seconds.',
            'backgroundSound.in' => 'Invalid background sound option.',
            'metadata.country.required' => 'Country is required.',
            'metadata.country.in' => 'Invalid country selection.',
            'metadata.assistant_phone_number.regex' => 'Assistant phone number must be in international format.',
            'metadata.webhook_url.url' => 'Webhook URL must be a valid URL.',
        ];
    }
}
