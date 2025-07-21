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
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Download</h2>
      <ul class="text-left space-y-2 text-indigo-300">
        <li><a href="https://drive.google.com/file/d/1yvzfjg_J_LNEGee1B7LqTYu-MkFJKV3N/view?usp=sharing" class="hover:text-white hover:underline">⬇️ Full Client</a></li>
      </ul>
    </div>
@endsection

