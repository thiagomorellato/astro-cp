@extends('layouts.app')

@section('content')
<div 
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 100)"
    x-show="show"
    x-transition:enter="transition ease-out duration-700"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak
    class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-sm mx-auto shadow-lg border border-white/20"
>
    <h2 class="text-2xl font-semibold text-center mb-4 text-yellow-500 font-['Cinzel'] drop-shadow">
        Support AstRO
    </h2>

    <p class="mb-4 text-center text-sm text-gray-300">
        Your donation helps keep the server alive and growing.
    </p>

    <div class="flex flex-col justify-center mb-2 space-y-4 max-w-xs mx-auto">
        <!-- PayPal Button -->
        <button onclick="handleDonateLoading(event)" id="donate-btn"
            class="cursor-pointer bg-gray-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded-lg shadow transition w-full flex items-center justify-center gap-2">
            <svg id="donate-spinner" class="hidden animate-spin h-5 w-5 text-white"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span id="donate-text" class="flex items-center gap-2">
                <i class="fab fa-paypal text-white text-lg"></i> Donate via PayPal
            </span>
        </button>

        <!-- PIX Button -->
        <button onclick="handlePixRedirect(event)" class="bg-gray-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded-lg shadow transition w-full text-center">
            Donate via PIX
        </button>
    </div>

    <p class="text-center text-xs text-gray-300 italic mb-4">
        1 USD = 1000 SP
    </p>
</div>

<!-- JS: Login status -->
<script>
    const isLoggedIn = @json(session()->has('astrocp_user'));

    function handleDonateLoading(event) {
        event.preventDefault();

        if (!isLoggedIn) {
            alert('You must be logged in to donate.');
            return;
        }

        const btn = document.getElementById('donate-btn');
        const spinner = document.getElementById('donate-spinner');
        const text = document.getElementById('donate-text');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        text.textContent = 'Redirecting...';

        setTimeout(() => {
            window.location.href = "{{ route('donations.paypal') }}";
        }, 500);
    }

    function handlePixRedirect(event) {
        event.preventDefault();

        if (!isLoggedIn) {
            alert('You must be logged in to donate.');
            return;
        }
    }
</script>
@endsection
