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
    class="max-w-md mx-auto mt-20 p-6 bg-green-700 rounded-xl text-white text-center shadow-lg"
>
    <h1 class="text-3xl font-bold mb-4 text-yellow-300 font-['Cinzel']">Success!</h1>
    <p class="text-base mb-6">Your order has been successfully registered.</p>
<p class="text-lg font-medium mb-4">
    @if (session('credits'))
        <span class="text-yellow-400 font-semibold">
            {{ session('credits') }}
        </span> Star Credits will be added as soon as your payment is confirmed.
    @else
        Your Star Credits will be added as soon as the payment is confirmed.
    @endif
</p>
    <a href="{{ url('/') }}" class="text-sm underline text-yellow-300 hover:text-yellow-400 transition">
        ← Back to Home
    </a>
</div>
@endsection
