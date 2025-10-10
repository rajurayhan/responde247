<?php

namespace App\Http\Middleware;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DetectTenantByDomain
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->getHost();

        // Cache reseller lookup for 5 minutes
        $cacheKey = "reseller_domain_{$domain}";
        $reseller = cache()->remember($cacheKey, 300, function () use ($domain) {
            return Reseller::where('domain', $domain)->active()->first();
        });

        if (! $reseller) {
            // Clear cache if reseller not found to prevent stale data
            cache()->forget($cacheKey);
            // abort(404, 'Reseller not found');
            return response()->view('errors.404_reseller_not_found', [], 200);
        }
        
        // Double-check that reseller still exists and is active
        if (!$reseller->exists || !$reseller->isActive()) {
            // Clear cache and return 404
            cache()->forget($cacheKey);
            Log::warning('Cached reseller is invalid or inactive', [
                'reseller_id' => $reseller->id ?? 'unknown',
                'domain' => $domain,
                'exists' => $reseller->exists ?? false,
                'active' => $reseller->isActive() ?? false
            ]);
            return response()->view('errors.404_reseller_not_found', [], 200);
        }
        
        // Cache reseller settings for 5 minutes
        $settingsCacheKey = "reseller_settings_{$reseller->id}";
        $resellerSettings = cache()->remember($settingsCacheKey, 300, function () use ($reseller) {
            return ResellerSetting::where('reseller_id', $reseller->id)->first();
        });

        // Share tenant globally (view, model, etc.)
        app()->instance('currentReseller', $reseller);
        app()->instance('currentResellerSettings', $resellerSettings ?? null);

        // Optional: set reseller in config or auth logic
        config(['reseller.id' => $reseller->id]);
        Log::info('Reseller ID: ' . $reseller->id);
        return $next($request);
    }
}
