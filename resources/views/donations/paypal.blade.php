@extends('layouts.app')

@section('content')
<div 
    x-data="donationForm()"
    x-init="init()"
    x-cloak
    class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-md mx-auto shadow-lg border border-white/20 mt-10"
>
    <h2 class="text-2xl font-semibold text-center mb-4 text-yellow-500 font-['Cinzel'] drop-shadow">
        Donate via PayPal
    </h2>

    <p class="text-sm text-center text-gray-300 mb-6">
        <span class="italic text-xs text-yellow-300">1 USD = 100 SC</span>
    </p>

    <form action="{{ route('paypal.buy') }}" method="GET" class="space-y-6">
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
                    class="w-full px-4 py-2 rounded bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500"
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
                    class="w-full px-4 py-2 rounded bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                >
            </div>
        </div>

        <!-- Hidden amount field -->
        <input type="hidden" name="amount" :value="usd.toFixed(2)" />

        <button 
            type="submit"
            class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-semibold py-2 rounded shadow transition"
        >
            Donate with PayPal
        </button>
    </form>
</div>

<script>
    function donationForm() {
        return {
            usd: 5,
            sc: 500,
            rate: 100, // 1 USD = 100 SC
            init() {
                this.syncFromUSD();
            },
            syncFromUSD() {
                this.sc = Math.round(this.usd * this.rate);
            },
            syncFromSC() {
                this.usd = (this.sc / this.rate).toFixed(2);
            }
        }
    }
</script>
@endsection
