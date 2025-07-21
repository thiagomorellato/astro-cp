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
    class="bg-white/10 backdrop-blur-md mb-4 text-white p-6 rounded-xl max-w-sm mx-auto shadow-lg border border-white/20"
    >
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Discord</h2>
            <p class="mt-4 text-sm text-white/80 mb-2">
                    <a href="https://discord.gg/GNMjPgynyv" target="_blank" class="text-yellow-300 hover:underline">
                Discord Server
            </a>
      </p>
      <h2 class="text-2xl font-['Cinzel'] text-white/80 mb-2">Rates</h2>
            <p class="mt-4 text-sm text-white/80 mb-2">
        30x/30x/30x
      </p>
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Features</h2>
<p class="mt-4 text-sm text-white/80 mb-2">
  <strong>4th Job Support (EP18)</strong><br>
  Play with full 4th job support up to Episode 18 (more to come).<br><br>

  <strong>No Pay to Win</strong><br>
  Fair gameplay focused on player effort and smart progression.<br><br>

  <strong>Stars System</strong><br>
  Shadow Gear has been completely removed and replaced with customizable Stars tailored to your class and build.<br><br>

  <strong>Starbot (Autoplay)</strong><br>
  1 hour of daily autoplay for all players (3 hours for VIPs). Perfect for those who can’t grind all day.<br><br>

  <strong>Custom Progression</strong><br>
  Designed for smoother leveling, better party synergy, and less meta-locking.<br><br>

  <strong>Active Development</strong><br>
  Regular updates and a dev team that listens to the community.
</p>

    </div>

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
      <h2 class="text-2xl font-['Cinzel'] text-yellow-500 mb-2">Patch Notes</h2>
            <p class="mt-4 text-sm text-white/80 mb-2">
      <strong>20/07/2025:</strong><br> - Server Launch
      </p>

    </div>
    
@endsection
