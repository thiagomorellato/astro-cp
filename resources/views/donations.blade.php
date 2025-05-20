@extends('layouts.app')

@section('content')
<div 
    x-data="donationPage()"
    x-init="init()"
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
        <button @click="attemptDonate('paypal')"
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
        <button @click="attemptDonate('pix')" 
            class="bg-gray-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded-lg shadow transition w-full text-center">
            Donate via PIX
        </button>
    </div>

    <p class="text-center text-xs text-gray-300 italic mb-4">
        1 USD = 1000 SP
    </p>

    <!-- Modal for login required -->
    <div 
        x-show="showLoginModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
        <div class="bg-white/10 backdrop-blur p-6 rounded-xl shadow-lg border border-white/20 max-w-sm w-full mx-4 text-white">
            <h3 class="text-xl font-semibold text-yellow-400 mb-4 text-center">Login Required</h3>
            <p class="text-sm text-gray-300 text-center mb-6">
                You must be logged in to make a donation.
            </p>
            <div class="flex justify-center gap-4">
                <button @click="redirectToLogin"
                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold shadow">
                    Go to Login
                </button>
                <button @click="showLoginModal = false"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold shadow">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function donationPage() {
        return {
            isLoggedIn: @json(session()->has('astrocp_user')),
            showLoginModal: false,
            init() {
                // Optional: intro animation or preload logic
            },
            attemptDonate(method) {
                if (!this.isLoggedIn) {
                    this.showLoginModal = true;
                    return;
                }

                if (method === 'paypal') {
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

                if (method === 'pix') {
                    window.location.href = "{{ route('account') }}";
                }
            },
            redirectToLogin() {
                window.location.href = "{{ route('account') }}"; // ou qualquer rota de login do seu sistema
            }
        }
    }
</script>
@endsection
