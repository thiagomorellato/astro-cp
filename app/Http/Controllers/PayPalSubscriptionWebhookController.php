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

    // subscriptionId pode vir em billing_agreement_id ou id
    $subscriptionId = $resource['billing_agreement_id'] ?? ($resource['id'] ?? null);

    if (!$subscriptionId) {
        return response()->json(['error' => 'Missing subscription ID'], 400);
    }

    $subscription = DB::connection('ragnarok')->table('subscriptions')->where('subscription_id', $subscriptionId)->first();

    if (!$subscription) {
        Log::warning("Subscription ID $subscriptionId not found in subscriptions");
        return response()->json(['error' => 'Subscription not found'], 404);
    }

    $accountId = $subscription->account_id;

    switch ($eventType) {
        case 'PAYMENT.SALE.COMPLETED':
            // Atualiza sub_status para active
            DB::connection('ragnarok')->table('subscriptions')
                ->where('subscription_id', $subscriptionId)
                ->update(['sub_status' => 'active']);

            // Atualiza vip_time
            $login = DB::connection('ragnarok')->table('login')->where('account_id', $accountId)->first();

            if (!$login) {
                Log::warning("User with ccount_id $accountId not found in login");
                return response()->json(['error' => 'User not found'], 404);
            }

            $daysInMonth = (int) date('t'); // dias no mês atual
            $secondsToAdd = $daysInMonth * 86400;

            $currentVipTime = (int) $login->vip_time;
            $now = time();

            $newVipTime = max($currentVipTime, $now) + $secondsToAdd;

            DB::connection('ragnarok')->table('login')
                ->where('account_id', $accountId)
                ->update(['vip_time' => $newVipTime]);

            Log::info("vip_time updated for account $accountId to $newVipTime");

            // Agora insere a doação na donations_pp
            $amountUsd = 0.00;
            if (isset($resource['amount']['total'])) {
                $amountUsd = isset($resource['amount']['total']) ? (float) $resource['amount']['total'] : 10.00;

            }

            DB::connection('ragnarok')->table('donations_pp')->insert([
                'account_id' => $accountId,
                'amount_usd' => $amountUsd,
                'credits' => 0, // ajustar se quiser outro valor
                'paypal_order_id' => null,
                'paypal_event_id' => $event['id'] ?? null,
                'status' => 'success',
                'paypal_subscription' => 'activated', // ou 'renewed' se quiser diferenciar
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            break;

        case 'BILLING.SUBSCRIPTION.EXPIRED':
            // Apenas marca como inativo
            DB::connection('ragnarok')->table('subscriptions')
                ->where('subscription_id', $subscriptionId)
                ->update(['sub_status' => 'inactive']);

            Log::info("Subscription $subscriptionId marked as inactive (expired)");
            break;

        default:
            return response()->json(['message' => 'Event ignored'], 200);
    }

    return response()->json(['message' => 'Subscription handled'], 200);
}


}

