<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalController extends Controller
{
    public function createOrder(Request $request)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post(config('services.paypal.base_url') . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $request->input('amount', '5.00'),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('paypal.success'),
                    'cancel_url' => route('paypal.cancel'),
                ],
            ]);

        $order = $response->json();

        return redirect(collect($order['links'])->firstWhere('rel', 'approve')['href']);
    }

    public function captureOrder(Request $request)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post(config('services.paypal.base_url') . '/v2/checkout/orders/' . $request->query('token') . '/capture');

        // Aqui você pode inserir os créditos na conta do jogador
        // Exemplo: incrementar 100 Star Credits

        return redirect('/account')->with('success', 'Purchase successful!');
    }

    public function cancel()
    {
        return redirect('/account')->with('error', 'Purchase canceled.');
    }

    private function getAccessToken()
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        )->post(config('services.paypal.base_url') . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        return $response->json()['access_token'];
    }
}
