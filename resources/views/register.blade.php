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
        <h2 class="text-2xl font-semibold mb-6 text-yellow-400 font-['Cinzel']">Create Account</h2>

        @if (session('success'))
            <div class="mb-4 text-green-400">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('astrocp.register') }}" onsubmit="handleRegisterLoading(event)">
            @csrf

            {{-- USERNAME --}}
            <input 
                type="text" 
                name="userid" 
                placeholder="Username" 
                value="{{ old('userid') }}"
                pattern="[a-zA-Z0-9]+"
                title="Username must contain only letters and numbers"
                minlength="4"
                maxlength="23"
                required
                class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" 
            />
            @error('userid')
                <p class="text-red-400 text-sm mb-3">{{ $message }}</p>
            @enderror

            {{-- PASSWORD --}}
            <input 
                type="password" 
                name="password" 
                placeholder="Password"
                minlength="6"
                maxlength="32"
                required
                class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" 
            />
            <input 
                type="password" 
                name="password_confirmation" 
                placeholder="Confirm Password"
                minlength="6"
                maxlength="32"
                required
                class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" 
            />
            @error('password')
                <p class="text-red-400 text-sm mb-3">{{ $message }}</p>
            @enderror

            {{-- EMAIL --}}
            <input 
                type="email" 
                name="email" 
                placeholder="Email" 
                value="{{ old('email') }}"
                maxlength="39"
                required
                class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" 
            />
            @error('email')
                <p class="text-red-400 text-sm mb-3">{{ $message }}</p>
            @enderror

            <button id="register-btn" type="submit"
                    class="w-full bg-gray-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded transition flex items-center justify-center gap-2">
                <svg id="register-spinner" class="hidden animate-spin h-5 w-5 text-white"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span id="register-text">Register</span>
            </button>
        </form>

        <p class="mt-4 text-sm text-white/80">
            Already have an account? <a href="{{ route('astrocp.login.form') }}" class="underline hover:text-yellow-400">Log in</a>
        </p>
    </div>

    <script>
        function handleRegisterLoading(event) {
            const btn = document.getElementById('register-btn');
            const spinner = document.getElementById('register-spinner');
            const text = document.getElementById('register-text');

            btn.disabled = true;
            spinner.classList.remove('hidden');
            text.textContent = 'Creating account...';
        }

        // Reforço JS para remover caracteres inválidos no userid
        document.addEventListener('DOMContentLoaded', () => {
            const useridInput = document.querySelector('input[name="userid"]');
            useridInput.addEventListener('input', () => {
                useridInput.value = useridInput.value.replace(/[^a-zA-Z0-9]/g, '');
            });
        });
    </script>
@endsection
