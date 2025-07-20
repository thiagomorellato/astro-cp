<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    public function createOrder(Request $request)
    {
        // 🔐 Check if user is logged in
        $userid = session('astrocp_user.userid');
        if (!$userid) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'User session expired. Please log in again.');
        }

        $accessToken = $this->getAccessToken();
        $value = number_format((float) $request->input('amount', 5.00), 2, '.', '');

        // 🔍 Retrieve account_id from user
        $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();

        if (!$user || !isset($user->account_id)) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Unable to find account information.');
        }

        $accountId = $user->account_id;

        // 📡 Create order on PayPal
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
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Failed to create PayPal order. ' . $response->body());
        }

        $order = $response->json();
        $paypalOrderId = $order['id'] ?? null;

        if (!$paypalOrderId) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Invalid PayPal order ID.');
        }

        // 💾 Save donation as "pending"
        DB::connection('ragnarok')->table('donations_pp')->insert([
            'account_id' => $accountId,
            'paypal_order_id' => $paypalOrderId,
            'amount_usd' => $value,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔗 Get approval URL
        $approveLink = collect($order['links'] ?? [])->firstWhere('rel', 'approve');

        if (!$approveLink || !isset($approveLink['href'])) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'No approval link returned from PayPal.');
        }

        return redirect($approveLink['href']);
    }

    public function captureOrder(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $orderId = $request->query('token');

        if (!$orderId) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Invalid PayPal token.');
        }

        $response = Http::withToken($accessToken)
            ->withBody('', 'application/json')
            ->post(config('services.paypal.base_url') . "/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Failed to capture PayPal order. ' . $response->body());
        }

        // 🔎 Find donation by order ID
        $donation = DB::connection('ragnarok')->table('donations_pp')
            ->where('paypal_order_id', $orderId)
            ->first();

        if (!$donation) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Donation record not found.');
        }

        // 💰 Calculate SC
        $rate = 1000; // 1 USD = 1000 SC
        $credits = (int) ($donation->amount_usd * $rate);

        // ✅ Update donation status
        DB::connection('ragnarok')->table('donations_pp')
            ->where('paypal_order_id', $orderId)
            ->update([
                'status' => 'captured',
                'updated_at' => now(),
            ]);

        // 📤 Flash SC amount to session
        return redirect('/donations/payment-successful')
            ->with('credits', $credits);
    }

    public function cancel(Request $request)
    {
        return redirect('/donations/payment-failed')
            ->with('error', 'Purchase canceled.');
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
