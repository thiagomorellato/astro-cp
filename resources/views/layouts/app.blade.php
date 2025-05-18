<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Astro-CP</title>
  @vite('resources/css/app.css')

  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-d+M7jMgjhHZMbbfZTtkGQm0fPfFGrckZ8+7+aPdlNHOeQGdCyZIQ41zylVo3GEz0wl0R0f2klm+Q67GlPiTHIQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak] { display: none !important; }
</style>
</head>
<body class="flex flex-col min-h-screen bg-gray-900 text-white font-['Poppins']" style="background-image: url('{{ asset('images/background.jpg') }}'); background-size: 100% 100%; background-position: center; min-height: 100vh;">

  <!-- Navbar -->
  <header class="fixed top-0 inset-x-0 z-50 bg-black/60 backdrop-blur-md shadow-md" x-data="{ mobileMenuOpen: false }">
    <nav class="max-w-screen-xl mx-auto px-6 py-4 flex items-center justify-center relative">
      
      <!-- Mobile menu button -->
      <div class="absolute left-4 md:hidden">
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Left links -->
      <div class="gap-4 items-center absolute left-40 hidden md:flex">
        <a href="/" class="hover:text-yellow-400 transition">Home</a>
        <a href="/account" class="hover:text-yellow-400 transition">Account</a>
        <a href="/donations" class="hover:text-yellow-400 transition">Donations</a>
      </div>

      <!-- Logo -->
      <div class="flex-shrink-0">
        <a href="/">
          <img src="{{ asset('images/logo.svg') }}" alt="Astro Logo" class="h-10 w-auto">
        </a>
      </div>

      <!-- Right links -->
      <div class="gap-4 items-center absolute right-40 hidden md:flex">
        <a href="/downloads" class="hover:text-yellow-400 transition">Downloads</a>
        <a href="/info" class="hover:text-yellow-400 transition">Info</a>
        <a href="/vote" class="hover:text-yellow-400 transition">Vote</a>
      </div>

      @if(session()->has('astrocp_user'))
        <div class="absolute right-40 bottom-1 text-sm text-white/80 hidden md:block">
          Welcome, {{ session('astrocp_user.userid') }}. 
          <form method="POST" action="{{ route('astrocp.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="underline hover:text-yellow-400 bg-transparent border-none p-0 m-0 cursor-pointer text-sm text-white">
              Logout
            </button>
          </form>
        </div>
      @endif

    </nav>

    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-black/80 backdrop-blur-md px-6 pb-4">
      <div class="flex flex-col items-center space-y-2 pt-2">
        <a href="/" class="hover:text-yellow-400 transition">Home</a>
        <a href="/account" class="hover:text-yellow-400 transition">Account</a>
        <a href="/donations" class="hover:text-yellow-400 transition">Donations</a>
        <a href="/downloads" class="hover:text-yellow-400 transition">Downloads</a>
        <a href="/info" class="hover:text-yellow-400 transition">Info</a>
        <a href="/vote" class="hover:text-yellow-400 transition">Vote</a>
      </div>

      @if(session()->has('astrocp_user'))
        <div class="text-sm text-right mt-4 mr-4">
          Welcome, {{ session('astrocp_user.userid') }}. 
          <form method="POST" action="{{ route('astrocp.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="underline hover:text-yellow-400 bg-transparent border-none p-0 m-0 cursor-pointer text-sm text-white">
              Logout
            </button>
          </form>
        </div>
      @endif

    </div>
  </header>

  <!-- Main content -->
  <div class="flex-grow pt-24 px-4 max-w-screen-xl mx-auto text-center">
    <img src="{{ asset('images/logofundo.svg') }}" alt="Astro Fundo Logo" class="h-[30vh] mx-auto mb-8">
    
    @yield('content')
  </div>

    <!-- Footer -->
    <footer class="bg-black/70 text-white text-xs sm:text-sm text-center py-4 mt-8">
      &copy; {{ date('Y') }} astRO. All rights reserved.
    </footer>

</body>
</html>
