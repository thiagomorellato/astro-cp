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
        Donate via PIX (ASAAS)
    </h2>

    <p class="text-sm text-center text-gray-300 mb-6">
        <span class="italic text-xs text-yellow-300">R$ 5,20 = 1000 SC</span>
    </p>

    <form action="{{ route('donations.asaas.create') }}" method="POST" class="space-y-6" @submit="handleLoading" x-ref="form">
        @csrf

        <div class="flex items-center gap-2">
            <!-- BRL input -->
            <div class="w-1/2">
                <label for="brl" class="block text-sm text-gray-300 mb-1">BRL</label>
                <input 
                    id="brl"
                    type="number" 
                    min="1" 
                    step="0.10" 
                    x-model="brl" 
                    @input="syncFromBRL" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                >
            </div>

            <!-- <=> Icon -->
            <div class="text-yellow-400 text-xl self-center font-bold select-none">⇄</div>

            <!-- SC input -->
            <div class="w-1/2">
                <label for="sc" class="block text-sm text-gray-300 mb-1">Star Credits (SC)</label>
                <input 
                    id="sc"
                    type="number" 
                    min="1000" 
                    step="1000" 
                    x-model="sc" 
                    @input="syncFromSC" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                >
            </div>
        </div>

        <!-- Hidden inputs -->
        <input type="hidden" name="amount" :value="Number(brl).toFixed(2)" />
        <input type="hidden" name="cpf" x-ref="hiddenCpf" />

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
            <span id="donate-text">Donate with PIX</span>
        </button>
    </form>

    <!-- Confirmation Modal -->
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
            <div class="mb-4">
                <label for="cpf_modal" class="block text-sm text-gray-300 mb-1">CPF</label>
                <input 
                    id="cpf_modal"
                    type="text" 
                    maxlength="14"
                    placeholder="000.000.000-00"
                    x-model="cpfModal" 
                    class="w-full px-4 py-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                    required
                >
            </div>
            <p class="text-sm text-center text-gray-200 mb-6">
                Are you sure you want to donate 
                <span class="font-bold text-yellow-300" x-text="'R$ ' + brl.toFixed(2)"></span>?
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
    function donationForm() {
        return {
            brl: 5.20,
            sc: 1000,
            rate: 192.31, // 1000 SC / 5.20 BRL
            show: false,
            showModal: false,
            cpfModal: '',
            formEl: null,

            init() {
                this.show = true;
                this.syncFromBRL();
            },
            syncFromBRL() {
                this.sc = Math.round(this.brl * this.rate);
            },
            syncFromSC() {
                this.brl = parseFloat((this.sc / this.rate).toFixed(2));
            },
            handleLoading(event) {
                event.preventDefault();
                this.formEl = event.target;
                this.showModal = true;
            },
            confirmDonation() {
                // Simple CPF validation: 11 digits numeric
                const cpf = this.cpfModal.replace(/\D/g, '');
                if (cpf.length !== 11) {
                    alert('Please enter a valid CPF with 11 digits.');
                    return;
                }

                // Copy CPF to hidden input
                this.$refs.hiddenCpf.value = this.cpfModal;

                this.showModal = false;

                const btn = document.getElementById('donate-btn');
                const spinner = document.getElementById('donate-spinner');
                const text = document.getElementById('donate-text');

                btn.disabled = true;
                spinner.classList.remove('hidden');
                text.textContent = 'Redirecting...';

                this.formEl.submit();
            }
        }
    }
</script>
@endsection
