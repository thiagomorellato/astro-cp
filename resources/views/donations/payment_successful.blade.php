@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-20 p-6 bg-green-700 rounded-xl text-white text-center shadow-lg">
    <h1 class="text-3xl font-bold mb-4">Payment Successful!</h1>
    <p class="text-lg mb-6">Thank you for your purchase.</p>
    <p class="text-xl font-semibold">
        You received <span class="text-yellow-400">{{ $credits }}</span> Star Credits.
    </p>
    <a href="{{ url('/') }}" class="mt-6 inline-block bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-2 px-4 rounded">
        Back to Home
    </a>
</div>
@endsection
