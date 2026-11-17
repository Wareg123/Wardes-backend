<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    public function sendNewOrder(User $seller, string $invoice): void
    {
        if (!$seller->fcm_token) return;
        try {
            app('firebase.messaging')->send(CloudMessage::withTarget('token',$seller->fcm_token)
                ->withNotification(Notification::create('Pesanan baru WARDES', "Pesanan {$invoice} masuk."))
                ->withData(['type'=>'new_order','invoice_number'=>$invoice]));
        } catch (\Throwable $e) {
            Log::warning('FCM send failed', ['user_id'=>$seller->id,'error'=>$e->getMessage()]);
        }
    }
}
