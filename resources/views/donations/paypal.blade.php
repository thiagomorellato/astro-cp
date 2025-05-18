@extends('layouts.app')

@section('content')
<div 
    x-data="donationForm()"
    x-init="init()"
    x-show="show"
    x-transition:enter="transition ease-out duration-700"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak
    class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-sm mx-auto shadow-lg border border-white/20"
>
    <h2 class="text-2xl font-semibold text-center mb-4 text-yellow-500 font-['Cinzel'] drop-shadow">
        Donate via PayPal
    </h2>

    <p class="text-sm text-center text-gray-300 mb-6">
        <span class="italic text-xs text-yellow-300">1 USD = 1000 SC</span>
    </p>

    <form action="{{ route('paypal.buy') }}" method="GET" class="space-y-6" @submit="handleLoading">
        <div class="flex items-center gap-2">
            <!-- USD input -->
            <div class="w-1/2">
                <label for="usd" class="block text-sm text-gray-300 mb-1">USD</label>
                <input 
                    type="number" 
                    min="1" 
                    step="0.01" 
                    x-model="usd" 
                    @input="syncFromUSD" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                >
            </div>

            <!-- <=> Icon -->
            <div class="text-yellow-400 text-xl align-middle font-bold select-none">⇄</div>

            <!-- SC input -->
            <div class="w-1/2">
                <label for="sc" class="block text-sm text-gray-300 mb-1">Star Credits (SC)</label>
                <input 
                    type="number" 
                    min="100" 
                    step="100" 
                    x-model="sc" 
                    @input="syncFromSC" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                >
            </div>
        </div>

        <!-- Hidden amount field -->
        <input type="hidden" name="amount" :value="Number(usd).toFixed(2)" />

        <button 
            type="submit"
            id="donate-btn"
            class="w-full bg-gray-500 hover:bg-yellow-600 text-white font-semibold py-2 px-6 rounded-lg shadow transition flex items-center justify-center gap-2"
        >
            <svg id="donate-spinner" class="hidden animate-spin h-5 w-5 text-white"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span id="donate-text">Donate with PayPal</span>
        </button>
    </form>
</div>

<script>
    function donationForm() {
        return {
            usd: 5,
            sc: 5000,
            rate: 1000,
            show: false,
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
                const btn = document.getElementById('donate-btn');
                const spinner = document.getElementById('donate-spinner');
                const text = document.getElementById('donate-text');

                btn.disabled = true;
                spinner.classList.remove('hidden');
                text.textContent = 'Redirecting...';
            }
        }
    }
</script>
@endsection
