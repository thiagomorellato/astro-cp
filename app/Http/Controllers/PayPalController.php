<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalController extends Controller
{
    
    public function createOrder(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $value = number_format((float) $request->input('amount', 5.00), 2, '.', '');
    
        // 🔐 Verifica se o usuário está logado
        $userid = session('astrocp_user.userid');
        if (!$userid) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'User session expired. Please log in again.');
        }
    
        // 🔍 Busca o account_id correspondente ao userid
        $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();
    
        if (!$user || !isset($user->account_id)) {
            return redirect('/donations/payment-cancelled')
                ->with('error', 'Unable to find account information.');
        }
    
        $accountId = $user->account_id;
    
        // 📡 Cria a ordem no PayPal
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
    
        // 💾 Salva no banco como pending
        DB::connection('ragnarok')->table('donations_pp')->insert([
            'account_id' => $accountId,
            'paypal_order_id' => $paypalOrderId,
            'amount_usd' => $value,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
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

        // Só redireciona o usuário dizendo que o pagamento foi capturado.
        return redirect('/donations/payment-successful')
            ->with('success', 'Payment captured. Your credits will be updated shortly.');
    }

    public function cancel(Request $request)
    {
        $paypalOrderId = $request->query('token') ?? null;

        // Aqui você pode registrar a doação cancelada, se quiser, ou deixar só redirecionar.
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
