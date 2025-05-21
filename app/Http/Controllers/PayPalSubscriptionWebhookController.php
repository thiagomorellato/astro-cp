<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayPalSubscriptionWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event_type');
        $data = $request->input('resource');

        if ($event === 'BILLING.SUBSCRIPTION.ACTIVATED' || $event === 'PAYMENT.SALE.COMPLETED') {
            $surname = $data['subscriber']['name']['surname'] ?? null;
            if (!$surname) return response('Missing userid', 400);

            $userid = $surname;
            $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();
            if (!$user) return response('User not found', 404);

            $now = Carbon::now();
            $daysInMonth = $now->daysInMonth;
            $currentVip = $user->vip_time > $now->timestamp ? Carbon::createFromTimestamp($user->vip_time) : $now;
            $newVipTime = $currentVip->addDays($daysInMonth)->timestamp;

            DB::connection('ragnarok')->table('login')
                ->where('userid', $userid)
                ->update(['vip_time' => $newVipTime]);

            return response('VIP updated', 200);
        }

        return response('Event ignored', 200);
    }
}
