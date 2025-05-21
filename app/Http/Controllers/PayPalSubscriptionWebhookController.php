<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalSubscriptionWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->all();

        Log::info('PayPal webhook received:', $event);

        if (!isset($event['event_type']) || !isset($event['resource'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventType = $event['event_type'];
        $resource = $event['resource'];
        $subscriptionId = $resource['id'] ?? null;

        if (!$subscriptionId) {
            return response()->json(['error' => 'Missing subscription ID'], 400);
        }

        $donation = DB::table('donations_pp')->where('subscription_id', $subscriptionId)->first();

        if (!$donation) {
            Log::warning("Subscription ID $subscriptionId not found in donations_pp");
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $accountId = $donation->account_id;

        // Status para atualizar
        $subStatus = null;

        switch ($eventType) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
            case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
            case 'BILLING.SUBSCRIPTION.UPDATED':
                $subStatus = 'active';

                // Só adiciona vip_time para status ativos
                $daysInMonth = (int) date('t'); // dias no mês atual
                $secondsToAdd = $daysInMonth * 86400;

                $login = DB::table('login')->where('userid', $accountId)->first();

                if (!$login) {
                    Log::warning("User with userid $accountId not found in login");
                    return response()->json(['error' => 'User not found'], 404);
                }

                $currentVipTime = (int) $login->vip_time;
                $now = time();

                $newVipTime = max($currentVipTime, $now) + $secondsToAdd;

                DB::table('login')
                    ->where('userid', $accountId)
                    ->update(['vip_time' => $newVipTime]);

                Log::info("vip_time updated for user $accountId to $newVipTime");
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                $subStatus = 'inactive';
                // Não modifica vip_time
                break;

            default:
                return response()->json(['message' => 'Event ignored'], 200);
        }

        DB::table('donations_pp')
            ->where('subscription_id', $subscriptionId)
            ->update(['sub_status' => $subStatus]);

        Log::info("Subscription $subscriptionId updated: status = $subStatus");

        return response()->json(['message' => 'Subscription updated'], 200);
    }

}

