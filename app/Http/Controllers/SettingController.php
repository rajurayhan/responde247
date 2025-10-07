<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Get all settings by group
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->get('group', 'assistant_templates');
        
        $settings = Setting::contentProtection()->byGroup($group)->get();
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Get a specific setting
     */
    public function show($key): JsonResponse
    {
        $setting = Setting::contentProtection()->where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    /**
     * Update a setting (admin only)
     */
    public function update(Request $request, $key): JsonResponse
    {
        if (!Auth::user() || !Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'value' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $setting = Setting::contentProtection()->where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        $setting->value = $request->value;
        if ($request->has('description')) {
            $setting->description = $request->description;
        }
        $setting->save();

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting updated successfully'
        ]);
    }

    /**
     * Get assistant templates (public endpoint)
     */
    public function getAssistantTemplates(): JsonResponse
    {
        // Get reseller from domain context
        $reseller = app('currentReseller');
        
        if (!$reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Reseller context not found'
            ], 400);
        }

        // Get templates for the current reseller
        $templates = [
            'system_prompt' => Setting::where('key', 'assistant_system_prompt_template')
                ->where('reseller_id', $reseller->id)
                ->first()?->typed_value ?? '',
            'first_message' => Setting::where('key', 'assistant_first_message_template')
                ->where('reseller_id', $reseller->id)
                ->first()?->typed_value ?? '',
            'end_call_message' => Setting::where('key', 'assistant_end_call_message_template')
                ->where('reseller_id', $reseller->id)
                ->first()?->typed_value ?? ''
        ];

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }
} 