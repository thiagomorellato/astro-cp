@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-20 p-6 bg-red-700 rounded-xl text-white text-center shadow-lg">
    <h1 class="text-3xl font-bold mb-4">Payment Failed or Cancelled</h1>
    <p class="text-lg mb-6">{{ session('error') ?? 'An unexpected error occurred.' }}</p>
    <a href="{{ url('/') }}" class="mt-6 inline-block bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-2 px-4 rounded">
        Back to Home
    </a>
</div>
@endsection
