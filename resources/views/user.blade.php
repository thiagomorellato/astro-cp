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

<div 

    x-data="{ tab: 'account', confirmDelete: false, confirmReset: false, confirmLook: false, selectedChar: null }"

    class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-2xl mx-auto shadow-lg border border-white/20"
>
    <div class="flex border-b border-white/20 mb-6">
        <button 
            @click="tab = 'account'"
            :class="tab === 'account' 
                ? 'border-b-2 border-yellow-400 text-yellow-400' 
                : 'text-gray-300 hover:text-white'" 
            class="py-2 px-4 text-sm font-semibold transition cursor-pointer"
        >
            Account
        </button>
        <button 
            @click="tab = 'chars'"
            :class="tab === 'chars' 
                ? 'border-b-2 border-yellow-400 text-yellow-400' 
                : 'text-gray-300 hover:text-white'" 
            class="py-2 px-4 text-sm font-semibold transition cursor-pointer"
        >
            Chars
        </button>
    </div>


    {{-- Account Tab --}}
    <div x-show="tab === 'account'" x-transition>
        <div class="space-y-4 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-300 font-semibold ">Account:</span>
                <span class="w-1/2 flex justify-center">{{ session('astrocp_user.userid') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-300 font-semibold">VIP Status:</span>
                @if($isVip)
                    <span class="text-green-400 font-bold">Active</span>
                    @else
                      <button onclick="openVipModal()" class="bg-gray-500 hover:bg-yellow-500 text-white text-xs py-1 px-2 rounded">
                        Subscribe
                      </button>
                    @endif
            </div>

        </div>
    </div>

    {{-- Chars Tab --}}
    <div x-show="tab === 'chars'" x-transition>
        <div class="space-y-3">
            @forelse($characters as $char)
            <div class="bg-gray-800/70 px-4 py-3 rounded-lg flex justify-between items-center border border-gray-600">
                <span>{{ $char->name }}</span>
                <div class="flex items-center gap-2">
                    {{-- Reset Look icon --}}
                    <button 
                        @click="selectedChar = '{{ $char->name }}'; confirmLook = true;" 
                        class="text-yellow-400 hover:text-yellow-500 cursor-pointer" 
                        title="Reset look"
                    >
                        🎭
                    </button>

                    {{-- Reset position icon --}}
                    <button 
                        @click="selectedChar = '{{ $char->name }}'; confirmReset = true;" 
                        class="text-yellow-400 hover:text-yellow-500 cursor-pointer" 
                        title="Reset position"
                    >
                        📍
                    </button>

                    {{-- Delete icon --}}
                    <button 
                        @click="selectedChar = '{{ $char->name }}'; confirmDelete = true;" 
                        class="text-red-400 hover:text-red-600 cursor-pointer" 
                        title="Delete character"
                    >
                        🗑️
                    </button>
                </div>
            </div>

            @empty
                <p class="text-center text-gray-400 italic">No characters found.</p>
            @endforelse

        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div 
        x-show="confirmDelete"
        x-transition
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50"
        x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-sm border border-white/20 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-yellow-500">Confirm Deletion</h3>
            <p class="text-sm text-gray-300">
                Are you sure you want to delete <span class="font-semibold text-white" x-text="selectedChar"></span>?
            </p>
            <p class="text-xs text-red-400">This action cannot be undone.</p>

            <form method="POST" action="{{ route('char.delete') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="char_name" :value="selectedChar">
                <div>
                    <label class="text-sm block text-gray-300 mb-1">Enter your password:</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring focus:ring-yellow-500">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmDelete = false" class="text-sm text-gray-300 hover:text-white">Cancel</button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 text-sm rounded-lg text-white font-semibold">Delete</button>
                </div>
            </form>
        </div>
    </div>
    {{-- Reset Position Modal --}}
    <div 
        x-show="confirmReset"
        x-transition
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50"
        x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-sm border border-white/20 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-yellow-500">Reset Position</h3>
            <p class="text-sm text-gray-300">
                Are you sure you want to reset <span class="font-semibold text-white" x-text="selectedChar"></span>'s position to Prontera?
            </p>
            <form method="POST" action="{{ route('char.resetPosition') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="char_name" :value="selectedChar">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmReset = false" class="text-sm text-gray-300 hover:text-white">Cancel</button>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-sm rounded-lg text-white font-semibold">Reset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset Look Modal --}}
    <div 
        x-show="confirmLook"
        x-transition
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50"
        x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-sm border border-white/20 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-yellow-500">Reset Look</h3>
            <p class="text-sm text-gray-300">
                Are you sure you want to reset <span class="font-semibold text-white" x-text="selectedChar"></span>'s appearance?
            </p>
            <form method="POST" action="{{ route('char.resetLook') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="char_name" :value="selectedChar">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmLook = false" class="text-sm text-gray-300 hover:text-white">Cancel</button>
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 px-4 py-2 text-sm rounded-lg text-white font-semibold">Reset</button>
                </div>
            </form>
        </div>
    </div>
    {{-- Vip Modal --}}
    <div 
        id="vipModal" 
        x-transition
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
        const response = await fetch("/paypal/subscribe/create", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ email })
        });
        const data = await response.json();
        if (data && data.approve_url) {
            window.location.href = data.approve_url;
        } else {
            alert("Failed to start subscription.");
        }
    }
</script>
@endsection
