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

@if ($errors->any())
    <div
        x-data="{ show: true }" 
        x-show="show"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="bg-red-700/90 text-white px-4 py-3 rounded-xl max-w-md mx-auto mb-4 shadow-lg border border-red-400/40 backdrop-blur"
    >
        <div class="text-sm text-center">
            <p class="font-bold mb-1">Please correct the following errors:</p>
            <ul class="list-none text-left">
                @foreach ($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif


<div 
    x-data="{ 
        tab: 'account', 
        confirmDelete: false, 
        confirmReset: false, 
        confirmLook: false, 
        selectedChar: null,
        changePasswordModalOpen: false,
        changeEmailModalOpen: false
    }"
    class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-2xl mx-auto shadow-lg border border-white/20"
>
    <div class="flex border-b border-white/20 mb-6">
        <button 
            @click="tab = 'account'; confirmDelete = false; confirmReset = false; confirmLook = false; changePasswordModalOpen = false; changeEmailModalOpen = false;"
            :class="tab === 'account' 
                ? 'border-b-2 border-yellow-400 text-yellow-400' 
                : 'text-gray-300 hover:text-white'" 
            class="py-2 px-4 text-sm font-semibold transition cursor-pointer focus:outline-none"
        >
            Account
        </button>
        <button 
            @click="tab = 'chars'; confirmDelete = false; confirmReset = false; confirmLook = false; changePasswordModalOpen = false; changeEmailModalOpen = false;"
            :class="tab === 'chars' 
                ? 'border-b-2 border-yellow-400 text-yellow-400' 
                : 'text-gray-300 hover:text-white'" 
            class="py-2 px-4 text-sm font-semibold transition cursor-pointer focus:outline-none"
        >
            Chars
        </button>
    </div>

    {{-- Account Tab --}}
    <div x-show="tab === 'account'" x-transition.opacity.duration.500ms>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center p-2 rounded-md bg-black/10">
                <span class="text-gray-300 font-semibold">Account ID:</span>
                <span class="text-gray-100 font-mono">{{ $userData->userid ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between items-center p-2 rounded-md bg-black/10">
                <span class="text-gray-300 font-semibold">Registered E-mail:</span>
                <span class="text-gray-100 font-mono">{{ $userData->email ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between items-center p-2 rounded-md bg-black/10">
                <span class="text-gray-300 font-semibold">VIP Status:</span>
                @if($isVip)
                    <span class="text-green-400 font-bold px-2 py-1 bg-green-500/20 rounded-md">Active</span>
                @else
                    {{-- Botão VIP original que você tinha --}}
                    <button onclick="openVipModal()" class="bg-gray-500 hover:bg-yellow-500 text-white text-xs py-2 w-1/2 rounded"> 
                        Subscribe
                    </button>
                @endif
            </div>

            {{-- Account Management Options --}}
            <div class="border-t border-white/10 pt-4 mt-5 space-y-2">
                 <h3 class="text-md font-semibold text-yellow-400 mb-2">Account Management</h3>
                <div class="flex justify-between items-center p-2 rounded-md hover:bg-white/5 transition">
                    <span class="text-gray-300">Change Password</span>
                    <button 
                        @click="changePasswordModalOpen = true"
                        class="text-sm text-yellow-400 hover:text-yellow-300 font-medium py-1 px-3 rounded-md border border-yellow-400/50 hover:border-yellow-300 transition"
                    >
                        Change
                    </button>
                </div>
                <div class="flex justify-between items-center p-2 rounded-md hover:bg-white/5 transition">
                    <span class="text-gray-300">Change E-mail</span>
                    <button 
                        @click="changeEmailModalOpen = true"
                        class="text-sm text-yellow-400 hover:text-yellow-300 font-medium py-1 px-3 rounded-md border border-yellow-400/50 hover:border-yellow-300 transition"
                    >
                        Change
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Chars Tab --}}
    <div x-show="tab === 'chars'" x-transition.opacity.duration.500ms>
        <div class="space-y-3">
            @forelse($characters as $char)
            <div class="bg-gray-800/70 px-4 py-3 rounded-lg flex justify-between items-center border border-gray-700/60 hover:border-gray-500/80 transition-all duration-150">
                <span class="font-medium text-gray-100">{{ $char->name }} <span class="text-xs text-gray-400">(Lvl: {{ $char->base_level ?? 'N/A' }} / Job: {{ $char->class ?? 'N/A' }})</span></span>
                <div class="flex items-center gap-2 text-lg">
                    <button 
                        @click="selectedChar = '{{ $char->name }}'; confirmLook = true;" 
                        class="text-yellow-400 hover:text-yellow-300 transition-colors duration-150 p-1 hover:bg-white/10 rounded-md" 
                        title="Reset look (Default)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2H4zm7.293 1.293a1 1 0 00-1.414 0L8 5.086V5a1 1 0 00-2 0v.086L4.121 3.207a1 1 0 00-1.414 1.414L4.586 6.5A4.003 4.003 0 004 10c0 .399.058.786.17 1.158L2.707 12.586a1 1 0 101.414 1.414L6 12.121V12a1 1 0 002 0v-.121l1.879 1.879a1 1 0 001.414-1.414L9.414 10.5A4.003 4.003 0 0010 7c.399 0 .786.058 1.158.17L12.586 9h.006l.328-.328A5.943 5.943 0 0110 7c-1.643 0-3.107.673-4.158 1.757A5.963 5.963 0 0110 4.037c.512 0 1 .103 1.459.293l1.834-1.835a1 1 0 00-1.293-1.495zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                    </button>
                    <button 
                        @click="selectedChar = '{{ $char->name }}'; confirmReset = true;" 
                        class="text-blue-400 hover:text-blue-300 transition-colors duration-150 p-1 hover:bg-white/10 rounded-md" 
                        title="Reset position (Prontera)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                    </button>
                    <button 
                        @click="selectedChar = '{{ $char->name }}'; confirmDelete = true;" 
                        class="text-red-400 hover:text-red-300 transition-colors duration-150 p-1 hover:bg-white/10 rounded-md" 
                        title="Delete character"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
            @empty
                <p class="text-center text-gray-400 italic py-4">No characters found for this account.</p>
            @endforelse
        </div>
    </div>

    {{-- Modais Existentes --}}
    {{-- Delete Confirmation Modal --}}
    <div 
        x-show="confirmDelete" x-transition
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
        @click.self="confirmDelete = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <h3 class="text-lg font-bold text-red-500 mb-2">Confirm Deletion</h3>
            <p class="text-sm text-gray-300 mb-4">
                Are you sure you want to delete character <strong class="text-yellow-400" x-text="selectedChar"></strong>?
            </p>
            <p class="text-xs text-gray-500">This action cannot be undone.</p>
            <form method="POST" action="{{ route('char.delete') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="char_name" :value="selectedChar">
                <div>
                    <label for="delete_char_password" class="text-sm block text-gray-300 mb-1">Enter your account password:</label>
                    <input id="delete_char_password" type="password" name="password" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-500/70 focus:border-yellow-500/0">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="confirmDelete = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700/60 transition">Cancel</button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Delete Character</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset Position Modal --}}
    <div 
        x-show="confirmReset" x-transition
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
        @click.self="confirmReset = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <h3 class="text-lg font-bold text-yellow-500 mb-2">Reset Position</h3>
            <p class="text-sm text-gray-300 mb-4">
                Are you sure you want to reset <strong class="text-yellow-400" x-text="selectedChar"></strong>'s position to Prontera?
            </p>
            <form method="POST" action="{{ route('char.resetPosition') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="char_name" :value="selectedChar">
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="confirmReset = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700/60 transition">Cancel</button>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Reset Position</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset Look Modal --}}
    <div 
        x-show="confirmLook" x-transition
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
        @click.self="confirmLook = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <h3 class="text-lg font-bold text-yellow-500 mb-2">Reset Look</h3>
            <p class="text-sm text-gray-300 mb-4">
                Are you sure you want to reset <strong class="text-yellow-400" x-text="selectedChar"></strong>'s appearance to default?
            </p>
            <form method="POST" action="{{ route('char.resetLook') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="char_name" :value="selectedChar">
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="confirmLook = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700/60 transition">Cancel</button>
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 px-4 py-2 text-sm rounded-lg text-black font-semibold transition">Reset Look</button>
                </div>
            </form>
        </div>
    </div>
    
    <div 
        id="vipModal" 
        x-transition {{-- x-transition pode não ter efeito com manipulação de classe 'hidden' --}}
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden"
        x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-sm border border-white/20 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-yellow-500">Subscribe to VIP</h3>
            <p class="text-sm text-gray-300">
                Subscribe for <span class="font-semibold text-white">$10 USD</span> per month to unlock VIP status and benefits.
            </p>
            
            <div>
                <label for="paypalEmail" class="text-sm block text-gray-300 mb-1">Enter your PayPal email:</label>
                <input 
                    type="email" 
                    id="paypalEmail" 
                    required 
                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring focus:ring-yellow-500"
                >
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeVipModal()" class="text-sm text-gray-300 hover:text-white">Cancel</button>
                <button 
                    onclick="startVipSubscription()" 
                    class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm rounded-lg text-white font-semibold"
                >
                    Subscribe
                </button>
            </div>
        </div>
    </div>

    {{-- MODAIS NOVOS PARA SENHA E EMAIL --}}
    {{-- Change Password Modal --}}
    <div 
        x-show="changePasswordModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 bg-black/70 flex items-center justify-center z-[51] p-4"
        @keydown.escape.window="changePasswordModalOpen = false"
        @click.self="changePasswordModalOpen = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-yellow-500">Change Password</h3>
                <button @click="changePasswordModalOpen = false" class="text-gray-400 hover:text-gray-200 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('account.changePassword') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="current_password_cp" class="text-sm block text-gray-300 mb-1">Current Password:</label>
                    <input id="current_password_cp" type="password" name="current_password" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-500/70 focus:border-transparent" autocomplete="current-password">
                </div>
                <div>
                    <label for="new_password_cp" class="text-sm block text-gray-300 mb-1">New Password:</label>
                    <input id="new_password_cp" type="password" name="new_password" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-500/70 focus:border-transparent" autocomplete="new-password">
                </div>
                <div>
                    <label for="new_password_confirmation_cp" class="text-sm block text-gray-300 mb-1">Confirm New Password:</label>
                    <input id="new_password_confirmation_cp" type="password" name="new_password_confirmation" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-500/70 focus:border-transparent" autocomplete="new-password">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="changePasswordModalOpen = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700/60 transition">Cancel</button>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change Email Modal --}}
    <div 
        x-show="changeEmailModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 bg-black/70 flex items-center justify-center z-[51] p-4"
        @keydown.escape.window="changeEmailModalOpen = false"
        @click.self="changeEmailModalOpen = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-yellow-500">Change E-mail</h3>
                <button @click="changeEmailModalOpen = false" class="text-gray-400 hover:text-gray-200 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('account.changeEmail') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="current_password_ce" class="text-sm block text-gray-300 mb-1">Current Password:</label>
                    <input id="current_password_ce" type="password" name="current_password" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-500/70 focus:border-transparent" autocomplete="current-password">
                </div>
                <div>
                    <label for="new_email_ce" class="text-sm block text-gray-300 mb-1">New E-mail:</label>
                    <input id="new_email_ce" type="email" name="new_email" value="{{ old('new_email', $userData->email ?? '') }}" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-500/70 focus:border-transparent" autocomplete="email">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="changeEmailModalOpen = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700/60 transition">Cancel</button>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function openVipModal() {
        document.getElementById('vipModal').classList.remove('hidden');
    }
    function closeVipModal() {
        document.getElementById('vipModal').classList.add('hidden');
    }
    async function startVipSubscription() {
        const email = document.getElementById('paypalEmail').value;
        // A rota abaixo deve existir e ser funcional no seu web.php
        const response = await fetch("/paypal/subscribe/create", { 
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}" // Mantendo seu método original de pegar o token
            },
            body: JSON.stringify({ email })
        });
        const data = await response.json();
        if (data && data.approve_url) {
            window.location.href = data.approve_url;
        } else {
            // Você pode querer dar um feedback de erro mais específico aqui se 'data.message' existir
            alert(data.message || "Failed to start subscription.");
        }
    }
</script>
@endsection