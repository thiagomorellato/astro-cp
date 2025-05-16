@extends('layouts.app')

@section('content')
    <div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-sm mx-auto shadow-lg border border-white/20 text-center">
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
