@extends('layouts.app')

@section('content')
    {{-- Feedback messages --}}
    @if(session('success'))
        <div 
            x-data="{ show: true }" 
            x-show="show"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-init="setTimeout(() => show = false, 4000)"
            class="bg-green-600/90 text-white px-4 py-3 rounded-xl max-w-md mx-auto mb-4 shadow-lg border border-green-300/40 backdrop-blur"
        >
            <p class="text-sm font-semibold text-center">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div 
            x-data="{ show: true }" 
            x-show="show"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-init="setTimeout(() => show = false, 4000)"
            class="bg-red-600/90 text-white px-4 py-3 rounded-xl max-w-md mx-auto mb-4 shadow-lg border border-red-300/40 backdrop-blur"
        >
            <p class="text-sm font-semibold text-center">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Painel com estilo translúcido branco --}}
    <div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-2xl mx-auto shadow-lg border border-white/20">
        <h2 class="text-xl font-bold mb-4 text-yellow-400 text-center">Upload de Itens</h2>

        <form action="{{ route('cash.shop.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="csv_file" class="block text-sm font-semibold text-gray-300 mb-1">Arquivo CSV:</label>
                <input 
                    type="file" 
                    name="csv_file" 
                    id="csv_file" 
                    accept=".csv" 
                    required 
                    class="w-full bg-gray-800 text-white border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-yellow-500"
                >
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm rounded-lg text-white font-semibold">
                    Enviar e Importar
                </button>
            </div>
        </form>
    </div>
@endsection
