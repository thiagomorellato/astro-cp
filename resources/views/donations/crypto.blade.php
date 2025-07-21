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

    @if ($errors->any())
        <div class="bg-red-500/50 text-white p-4 rounded-lg mb-4 border border-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                    :min="minUsd"
                    step="0.10" 
                    x-model.number="usd" 
                    @input="syncFromUSD" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                >
            </div>

            <div class="text-yellow-400 text-xl self-center font-bold select-none">⇄</div>

            <div class="w-1/2">
                <label for="sc" class="block text-sm text-gray-300 mb-1">Star Credits (SC)</label>
                <input 
                    type="number" 
                    :min="minUsd * rate"
                    step="1000" 
                    x-model.number="sc" 
                    @input="syncFromSC" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                >
            </div>
        </div>
        
        <div>
            <label for="pay_currency" class="block text-sm text-gray-300 mb-1">Select Crypto</label>
            <select name="pay_currency" required x-model="selectedCurrency"
                class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm">
                <option value="btc">Bitcoin (BTC)</option>
                <option value="busdt">USDT (BEP20)</option>
                <option value="usdttrc20">USDT (TRC20)</option>
                <option value="eth">Ethereum (ETH)</option>
                <option value="ltc">Litecoin (LTC)</option>
            </select>
        </div>
        
        <div class="text-center text-xs text-yellow-300 h-4" x-text="`Minimum for ${selectedCurrency.toUpperCase()}: $${minUsd}`"></div>

        <input type="hidden" name="amount" :value="Number(usd).toFixed(2)" />
        <input type="hidden" name="account_id" value="{{ session('astrocp_user.userid') }}" />

        <button 
            type="submit"
            id="crypto-donate-btn"
            class="w-full bg-gray-500 hover:bg-yellow-600 text-white font-semibold py-2 px-6 rounded-lg shadow transition flex items-center justify-center gap-2"
        >
            <svg id="crypto-donate-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span id="crypto-donate-text">Donate with Crypto</span>
        </button>
    </form>

    <div 
        x-show="showModal" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black/70 backdrop-blur-sm z-50"
    >
        <div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl shadow-lg border border-yellow-500 w-full max-w-sm">
            <h3 class="text-xl font-semibold text-yellow-400 mb-3 text-center font-['Cinzel'] drop-shadow">
                Confirm Donation
            </h3>
            <p class="text-sm text-center text-gray-200 mb-6">
                Are you sure you want to donate 
                <span class="font-bold text-yellow-300" x-text="'$' + Number(usd).toFixed(2)"></span>?
            </p>
            <div class="flex justify-center gap-4">
                <button 
                    type="button" 
                    @click="showModal = false"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg transition"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    @click="confirmDonation"
                    class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg font-semibold transition"
                >
                    Yes, donate
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function donationFormCrypto() {
        return {
            usd: 30, // O valor inicial será definido dinamicamente no init()
            sc: 30000,
            rate: 1000,
            show: false,
            showModal: false,
            formEl: null,
            selectedCurrency: 'btc', // Padrão é a primeira opção da lista (Bitcoin)

            // Propriedade "computada" que sempre retorna o valor mínimo correto
            get minUsd() {
                return this.selectedCurrency === 'btc' ? 30 : 20;
            },

            init() {
                this.show = true;
                // Ao carregar, o valor em USD já começa com o mínimo correto
                this.usd = this.minUsd; 
                this.syncFromUSD();

                // Observa mudanças na moeda e ajusta o valor em USD se ele ficar abaixo do novo mínimo
                this.$watch('selectedCurrency', () => {
                    if (this.usd < this.minUsd) {
                        this.usd = this.minUsd;
                        this.syncFromUSD();
                    }
                })
            },
            syncFromUSD() {
                if(this.usd === null) this.usd = 0;
                this.sc = Math.round(this.usd * this.rate);
            },
            syncFromSC() {
                if(this.sc === null) this.sc = 0;
                this.usd = parseFloat((this.sc / this.rate).toFixed(2));
            },
            handleLoading(event) {
                event.preventDefault();
                this.formEl = event.target;
                
                // Valida o valor contra o mínimo dinâmico antes de abrir o modal
                if (this.usd >= this.minUsd) {
                    this.showModal = true;
                } else {
                    // O alerta é específico para a moeda selecionada
                    alert(`The minimum donation for ${this.selectedCurrency.toUpperCase()} is $${this.minUsd}.`);
                }
            },
            confirmDonation() {
                this.showModal = false;

                const btn = document.getElementById('crypto-donate-btn');
                const spinner = document.getElementById('crypto-donate-spinner');
                const text = document.getElementById('crypto-donate-text');

                btn.disabled = true;
                spinner.classList.remove('hidden');
                text.textContent = 'Redirecting...';

                // Envia o formulário que foi guardado na variável 'formEl'
                this.formEl.submit();
            }
        }
    }
</script>
@endsection