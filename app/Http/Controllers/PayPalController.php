<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PayPalController extends Controller
{
    public function createOrder(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $value = number_format((float) $request->input('amount', 5.00), 2, '.', '');

        $response = Http::withToken($accessToken)
            ->post(config('services.paypal.base_url') . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $value,
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('paypal.success'),
                    'cancel_url' => route('paypal.cancel'),
                ],
            ]);

        if ($response->failed()) {
            return redirect('/donations/payment-cancelled')->with('error', 'Failed to create PayPal order. ' . $response->body());
        }

        $order = $response->json();
        $approveLink = collect($order['links'] ?? [])->firstWhere('rel', 'approve');

        if (!$approveLink || !isset($approveLink['href'])) {
            return redirect('/donations/payment-cancelled')->with('error', 'No approval link returned from PayPal.');
        }

        return redirect($approveLink['href']);
    }

    public function captureOrder(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $orderId = $request->query('token');

        if (!$orderId) {
            return redirect('/donations/payment-cancelled')->with('error', 'Invalid PayPal token.');
        }

        $response = Http::withToken($accessToken)
            ->withBody('', 'application/json')
            ->post(config('services.paypal.base_url') . "/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            return redirect('/donations/payment-cancelled')->with('error', 'Failed to capture PayPal order. ' . $response->body());
        }

        $orderData = $response->json();
        $amount = (float) ($orderData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0);
        $paypalOrderId = $orderData['id'] ?? null;

        if ($amount <= 0) {
            return redirect('/donations/payment-cancelled')->with('error', 'Invalid payment amount.');
        }

        $credits = intval($amount * config('services.paypal.conversion_rate', 1000));

        $user = session('astrocp_user');
        if (!$user) {
            return redirect('/donations/payment-cancelled')->with('error', 'User session not found.');
        }

        $login = DB::connection('ragnarok')->table('login')->where('userid', $user['userid'])->first();
        if (!$login) {
            return redirect('/donations/payment-cancelled')->with('error', 'User not found in login table.');
        }

        $accountId = $login->account_id; // <-- aqui, pega o account_id!

        // Atualiza os créditos
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

        // Registra a doação como sucesso
        DB::connection('ragnarok')->table('donations_pp')->insert([
            'account_id' => $accountId,
            'amount_usd' => $amount,
            'credits' => $credits,
            'paypal_order_id' => $paypalOrderId,
            'status' => 'success',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/donations/payment-successful')->with('success', "Purchase successful! You received {$credits} Star Credits.");
    }

    public function cancel(Request $request)
    {
        $paypalOrderId = $request->query('token') ?? null;

        $user = session('astrocp_user');
        if ($user && $paypalOrderId) {
            $login = DB::connection('ragnarok')->table('login')->where('userid', $user['userid'])->first();
            if ($login) {
                $accountId = $login->account_id;

                DB::connection('ragnarok')->table('donations_pp')->insert([
                    'account_id' => $accountId,
                    'amount_usd' => 0,
                    'credits' => 0,
                    'paypal_order_id' => $paypalOrderId,
                    'status' => 'cancelled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect('/donations/payment-cancelled')->with('error', 'Purchase canceled.');
    }

    private function getAccessToken()
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        )->post(config('services.paypal.base_url') . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            abort(500, 'Failed to authenticate with PayPal: ' . $response->body());
        }

        return $response->json()['access_token'] ?? null;
    }
}
