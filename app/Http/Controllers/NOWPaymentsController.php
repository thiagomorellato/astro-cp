<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NOWPaymentsController extends Controller
{
    public function showCryptoForm()
    {
        return view('donations.crypto');
    }

    public function createDonation(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:20',
            'pay_currency' => 'required|string',
            'account_id' => 'required|string',
        ]);

        $apiKey = config('services.nowpayments.key');
        $apiUrl = rtrim(config('services.nowpayments.url'), '/') . '/invoice';

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
        ])->post($apiUrl, [
            'price_amount'      => $request->amount,
            'price_currency'    => 'USD',
            'pay_currency'      => $request->pay_currency,
            'order_id'          => 'astro-' . $request->account_id,
            'ipn_callback_url'  => route('nowpayments.webhook'),
            'success_url'       => url('/donation/success'),
            'cancel_url'        => url('/donation/cancel'),
        ]);

        if ($response->successful()) {
            return redirect()->away($response->json()['invoice_url']);
        }

        Log::error('NOWPayments invoice creation failed', [
            'response' => $response->json(),
        ]);

        return response()->json([
            'error' => 'Failed to create donation invoice',
            'details' => $response->json(),
        ], 500);
    }

    public function webhook(Request $request)
    {
        $data = $request->all();

        Log::info('NOWPayments webhook received', $data);

        if (($data['payment_status'] ?? null) === 'finished') {
            $orderId = $data['order_id'] ?? '';
            $accountId = str_replace('astro-', '', $orderId);
            $usdAmount = floatval($data['price_amount'] ?? 0);
            $scAmount = intval($usdAmount * 1000);

            DB::table('login')->where('userid', $accountId)->increment('cash_points', $scAmount);

            Log::info("NOWPayments: Donation confirmed. Account: {$accountId}, Amount: \${$usdAmount}, SC: {$scAmount}");
        }

        return response('OK');
    }
}
