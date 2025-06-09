@extends('layouts.app')

@section('content')
<div 
    x-data="donationFormCrypto()"
    x-init="init()"
    x-show="show"
    x-transition:enter="transition ease-out duration-700"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak
    class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-sm mx-auto shadow-lg border border-white/20"
>
    <h2 class="text-2xl font-semibold text-center mb-4 text-yellow-500 font-['Cinzel'] drop-shadow">
        Donate with Crypto
    </h2>

    <p class="text-sm text-center text-gray-300 mb-6">
        <span class="italic text-xs text-yellow-300">1 USD = 1000 SC</span>
    </p>

    <form action="{{ route('nowpayments.buy') }}" method="POST" class="space-y-6" @submit="handleLoading">
        @csrf

        <div class="flex items-center gap-2">
            <div class="w-1/2">
                <label for="usd" class="block text-sm text-gray-300 mb-1">USD</label>
                <input 
                    type="number" 
                    min="20" 
                    step="0.10" 
                    x-model="usd" 
                    @input="syncFromUSD" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600"
                >
            </div>

            <div class="text-yellow-400 text-xl self-center font-bold select-none">⇄</div>

            <div class="w-1/2">
                <label for="sc" class="block text-sm text-gray-300 mb-1">Star Credits (SC)</label>
                <input 
                    type="number" 
                    min="20000" 
                    step="1000" 
                    x-model="sc" 
                    @input="syncFromSC" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600"
                >
            </div>
        </div>

        <div>
            <label for="pay_currency" class="block text-sm text-gray-300 mb-1">Select Crypto</label>
            <select name="pay_currency" required
                class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600">
                <option value="usdttrc20">USDT (TRC20)</option>
                <option value="btc">Bitcoin (BTC)</option>
                <option value="eth">Ethereum (ETH)</option>
                <option value="ltc">Litecoin (LTC)</option>
            </select>
        </div>

        <input type="hidden" name="amount" :value="Number(usd).toFixed(2)" />
        <input type="hidden" name="account_id" value="{{ session('astrocp_user.userid') }}" />

        <button 
            type="submit"
            id="crypto-donate-btn"
            class="w-full bg-gray-500 hover:bg-yellow-600 text-white font-semibold py-2 px-6 rounded-lg shadow flex items-center justify-center gap-2"
        >
            <svg id="crypto-donate-spinner" class="hidden animate-spin h-5 w-5 text-white"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span id="crypto-donate-text">Donate with Crypto</span>
        </button>
    </form>
</div>

<script>
    function donationFormCrypto() {
        return {
            usd: 20,
            sc: 20000,
            rate: 1000,
            show: false,
            formEl: null,
            init() {
                this.show = true;
                this.syncFromUSD();
            },
            syncFromUSD() {
                this.sc = Math.round(this.usd * this.rate);
            },
            syncFromSC() {
                this.usd = parseFloat((this.sc / this.rate).toFixed(2));
            },
            handleLoading(event) {
                event.preventDefault();
                this.formEl = event.target;

                const btn = document.getElementById('crypto-donate-btn');
                const spinner = document.getElementById('crypto-donate-spinner');
                const text = document.getElementById('crypto-donate-text');

                btn.disabled = true;
                spinner.classList.remove('hidden');
                text.textContent = 'Redirecting...';

                this.formEl.submit();
            }
        }
    }
</script>
@endsection
