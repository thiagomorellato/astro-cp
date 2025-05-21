<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalSubscriptionController extends Controller
{
    public function create(Request $request)
    {
        $userid = session('astrocp_user.userid');
        if (!$userid || !$request->email) {
            return response()->json(['error' => 'Missing user or email'], 400);
        }

        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post(config('services.paypal.base_url') . '/v1/billing/subscriptions', [
                'plan_id' => 'P-5JK91908E08174815NAW6O4Q', // Use seu ID de plano
                'subscriber' => [
                    'name' => [
                        'given_name' => 'Player',
                        'surname' => $userid
                    ],
                    'email_address' => $request->email,
                ],
                'application_context' => [
                    'brand_name' => 'AstRO',
                    'locale' => 'en-US',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => 'https://astro-cp.onrender.com/user',
                    'cancel_url' => 'https://astro-cp.onrender.com/user',
                ]
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Subscription creation failed'], 500);
        }

        $data = $response->json();
        $approveUrl = collect($data['links'])->firstWhere('rel', 'approve')['href'] ?? null;

        return response()->json(['approve_url' => $approveUrl]);
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
            abort(500, 'Failed to authenticate with PayPal.');
        }

        return $response->json()['access_token'] ?? null;
    }
}
