@extends('layouts.app')

@section('content')

    <!-- Login box -->
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

        <h2 class="text-2xl font-semibold mb-6 text-yellow-400 font-['Cinzel']">Login</h2>

        <form method="POST" action="{{ route('astrocp.login') }}" onsubmit="handleLoginLoading(event)">
            @csrf
            <input type="text" name="userid" placeholder="Username"
                   class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" />
            <input type="password" name="password" placeholder="Password"
                   class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" />
            <button id="login-btn" type="submit"
                    class="w-full bg-gray-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded transition flex items-center justify-center gap-2">
                <svg id="login-spinner" class="hidden animate-spin h-5 w-5 text-white"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span id="login-text">Log In</span>
            </button>
        </form>

        @if ($errors->any())
            <div class="mt-4 text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <p class="mt-4 text-sm text-white/80">
            New here? <a href="{{ route('astrocp.register.form') }}" class="underline hover:text-yellow-400">Create Account</a>
        </p>
    </div>

    <script>
        function handleLoginLoading(event) {
            const btn = document.getElementById('login-btn');
            const spinner = document.getElementById('login-spinner');
            const text = document.getElementById('login-text');

            btn.disabled = true;
            spinner.classList.remove('hidden');
            text.textContent = 'Logging in...';
        }
    </script>
@endsection
