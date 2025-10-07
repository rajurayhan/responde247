<?php

namespace App\Traits;

use App\Services\ResellerMailManager;

trait ResellerMailTrait
{
    /**
     * Send the given notification.
     */
    public function sendNotification($instance)
    {
        // Set mail configuration based on user's reseller
        if (method_exists($this, 'getResellerId')) {
            $reseller = \App\Models\Reseller::find($this->getResellerId());
            if ($reseller) {
                ResellerMailManager::setMailConfig($reseller);
            }
        }

        // Call the original notification method
        parent::sendNotification($instance);
    }
    
    /**
     * Get the reseller ID for the notifiable entity
     */
    public function getResellerId()
    {
        return $this->reseller_id ?? null;
    }
}
