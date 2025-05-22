@extends('layouts.app')

@section('content')
<div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-md mx-auto shadow-lg border border-white/20 mt-10">
    <h2 class="text-lg font-semibold mb-4 text-center text-yellow-400">Update Cash Shop DB</h2>

    <form method="POST" action="{{ route('cash.shop.import') }}">
        @csrf
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg font-semibold transition">
            Update DB
        </button>
    </form>
</div>
@endsection
