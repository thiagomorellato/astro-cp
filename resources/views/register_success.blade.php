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
        <h2 class="text-2xl font-semibold mb-4 text-yellow-400 font-['Cinzel']">Create Account</h2>

        <p class="text-white/90 text-md mb-6">
            Your account has been created.<br>
            <span class="text-yellow-300">May your journey among the stars begin.</span>
        </p>

        <p class="mt-4 text-sm text-white/70">
        <a href="{{ route('astrocp.login.form') }}" class="underline hover:text-yellow-400">Return to login</a>
        </p>
    </div>
@endsection
