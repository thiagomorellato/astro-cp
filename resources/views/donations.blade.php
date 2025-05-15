@extends('layouts.app')

@section('content')
  <div class="bg-white/10 backdrop-blur-md border border-white/20 text-white p-6 rounded-xl max-w-xl mx-auto shadow-lg">
    <h2 class="text-2xl font-semibold text-center mb-4 text-yellow-600 font-['Cinzel'] drop-shadow">
      Support AstRO
    </h2>

    <p class="mb-4 text-center text-sm text-gray-300">
      Your donation helps keep the server alive and evolving.
    </p>

<div class="flex flex-col justify-center mb-2 space-y-4 max-w-xs mx-auto">
  <a href="#" class="bg-gray-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded-lg shadow transition w-full text-center">
    Donate via PayPal
  </a>
  <a href="#" class="bg-gray-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded-lg shadow transition w-full text-center">
    Donate via PIX
  </a>
</div>

    <p class="text-center text-xs text-gray-300 italic mb-4">
       1 USD = 1000 SP
    </p>
  </div>
@endsection
