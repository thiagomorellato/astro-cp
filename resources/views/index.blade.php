@extends('layouts.app')

@section('content')
<p 
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 200)"
    x-show="show"
    x-transition:enter="transition ease-out duration-700"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak
    class="text-3xl text-yellow-400 font-['Cinzel'] mb-8 px-4 inline-block bg-black/50 backdrop-blur-sm rounded-lg shadow-xl"
>
    Only the brave dare tread among the stars.
</p>
@endsection

