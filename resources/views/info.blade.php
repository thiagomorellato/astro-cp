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
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Discord</h2>
            <p class="mt-4 text-sm text-white/80 mb-2">
                    <a href="https://discord.gg/GNMjPgynyv" target="_blank" class="text-yellow-300 hover:underline">
                Discord Server
            </a>
      </p>
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Rates</h2>
            <p class="mt-4 text-sm text-white/80 mb-2">
        30x/30x/30x
      </p>
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Features</h2>
            <p class="mt-4 text-sm text-white/80 mb-2">
        4a classe, custom systems, ep18
      </p>

    </div>
    
@endsection
