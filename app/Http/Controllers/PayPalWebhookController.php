<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    /**
     * Recebe o webhook do PayPal e processa o evento.
     */
    public function handle(Request $request)
    {
        $body = $request->getContent(); // Corpo cru necessário para verificação

        // Decodifica JSON para log e processamento
        $payload = json_decode($body, true);

        Log::info('Received PayPal webhook:', ['payload' => $payload]);

        $headers = [
            'paypal-transmission-id' => $request->header('PayPal-Transmission-Id'),
            'paypal-transmission-time' => $request->header('PayPal-Transmission-Time'),
            'paypal-cert-url' => $request->header('PayPal-Cert-Url'),
            'paypal-auth-algo' => $request->header('PayPal-Auth-Algo'),
            'paypal-transmission-sig' => $request->header('PayPal-Transmission-Sig'),
            'webhook-id' => config('services.paypal.webhook_id'),
        ];

        if (!$this->validateWebhook($headers, $body)) {
            Log::warning('Invalid PayPal webhook signature.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if (!$payload) {
            Log::warning('Invalid JSON payload from PayPal.');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventType = $payload['event_type'] ?? null;

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $payload['resource'] ?? [];

            $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
            $amount = $resource['amount']['value'] ?? null;
            $currency = $resource['amount']['currency_code'] ?? null;

            if (!$paypalOrderId || !$amount || !$currency) {
                Log::warning('Missing order_id, amount or currency in webhook.');
                return response()->json(['error' => 'Incomplete payment data'], 400);
            }

            $donation = DB::connection('ragnarok')->table('donations_pp')
                ->where('paypal_order_id', $paypalOrderId)
                ->first();

            if (!$donation) {
                Log::warning("Donation not found. PayPal Order ID: {$paypalOrderId}");
                return response()->json(['error' => 'Donation record not found'], 404);
            }

            if ($donation->status === 'success') {
                return response()->json(['message' => 'Donation already processed'], 200);
            }

            $accountId = $donation->account_id;

            if (!$accountId) {
                Log::error("Invalid account ID for donation ID {$donation->id}");
                return response()->json(['error' => 'Invalid account ID'], 400);
            }

            $conversionRate = config('services.paypal.conversion_rate', 1000);
            $credits = (int) floor(floatval($amount) * $conversionRate);

            $existing = DB::connection('ragnarok')->table('acc_reg_num')
                ->where('account_id', $accountId)
                ->where('key', '#CASHPOINTS')
                ->first();

            if ($existing) {
                DB::connection('ragnarok')->table('acc_reg_num')
                    ->where('account_id', $accountId)
                    ->where('key', '#CASHPOINTS')
                    ->update([
                        'value' => $existing->value + $credits,
                    ]);
            } else {
                DB::connection('ragnarok')->table('acc_reg_num')->insert([
                    'account_id' => $accountId,
                    'key' => '#CASHPOINTS',
                    'value' => $credits,
                ]);
            }

            DB::connection('ragnarok')->table('donations_pp')
                ->where('id', $donation->id)
                ->update([
                    'amount_usd' => $amount,
                    'credits' => $credits,
                    'status' => 'success',
                    'updated_at' => now(),
                ]);

            Log::info("Donation processed: PayPal Order {$paypalOrderId}, Account {$accountId}, USD {$amount}, Credits {$credits}");

            return response()->json(['message' => 'Payment processed successfully']);
        }

        return response()->json(['message' => 'Event ignored'], 200);
    }

    /**
     * Valida o webhook com a API do PayPal.
     */
    private function validateWebhook(array $headers, string $body): bool
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error('Failed to get PayPal access token for webhook validation.');
            return false;
        }

        $verificationPayload = [
            'auth_algo' => $headers['paypal-auth-algo'],
            'cert_url' => $headers['paypal-cert-url'],
            'transmission_id' => $headers['paypal-transmission-id'],
            'transmission_sig' => $headers['paypal-transmission-sig'],
            'transmission_time' => $headers['paypal-transmission-time'],
            'webhook_id' => $headers['webhook-id'],
            'webhook_event' => json_decode($body, true),
        ];

        Log::info('Sending verification payload to PayPal:', $verificationPayload);

        $response = Http::withToken($accessToken)->post(
            config('services.paypal.base_url') . '/v1/notifications/verify-webhook-signature',
            $verificationPayload
        );

        Log::info('PayPal webhook verification response:', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->successful()) {
            return $response->json()['verification_status'] === 'SUCCESS';
        }

        Log::error('Webhook signature verification failed.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    /**
     * Obtém o token OAuth do PayPal.
     */
    private function getAccessToken()
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        )->post(config('services.paypal.base_url') . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            Log::error('PayPal authentication failed: ' . $response->body());
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }
}
