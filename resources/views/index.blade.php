<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Astro-CP</title>
  @vite('resources/css/app.css')

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet" />

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-900 text-white font-['Poppins']" style="background-image: url('{{ asset('images/background.jpg') }}'); background-size: 100% 100%; background-position: center; min-height: 100vh;">

  <!-- Navbar black transparent -->
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

      <!-- Left links (desktop only) -->
      <div class="gap-4 items-center absolute left-40 hidden md:flex">
        <a href="#" class="hover:text-indigo-400 transition">Home</a>
        <a href="#" class="hover:text-indigo-400 transition">Account</a>
        <a href="#" class="hover:text-indigo-400 transition">Donations</a>
      </div>

      <!-- Centralized logo -->
      <div class="flex-shrink-0">
        <a href="#">
          <img src="{{ asset('images/logo.svg') }}" alt="Astro Logo" class="h-10 w-auto">
        </a>
      </div>

      <!-- Right links (desktop only) -->
      <div class="gap-4 items-center absolute right-40 hidden md:flex">
        <a href="#" class="hover:text-indigo-400 transition">Downloads</a>
        <a href="#" class="hover:text-indigo-400 transition">Info</a>
        <a href="#" class="hover:text-indigo-400 transition">Vote</a>
      </div>
    </nav>

    <!-- Mobile menu dropdown -->
    <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-black/80 backdrop-blur-md px-6 pb-4">
      <div class="flex flex-col items-center space-y-2 pt-2">
        <a href="#" class="hover:text-indigo-400 transition">Home</a>
        <a href="#" class="hover:text-indigo-400 transition">Account</a>
        <a href="#" class="hover:text-indigo-400 transition">Donations</a>
        <a href="#" class="hover:text-indigo-400 transition">Downloads</a>
        <a href="#" class="hover:text-indigo-400 transition">Info</a>
        <a href="#" class="hover:text-indigo-400 transition">Vote</a>
      </div>
    </div>
  </header>

  <!-- Main content -->
  <main class="pt-24 px-6 max-w-screen-xl mx-auto text-center">
    <img src="{{ asset('images/logofundo.svg') }}" alt="Astro Logo" class="h-[30vh] mx-auto mb-8">
    <p class="text-3xl text-yellow-400 font-['Cinzel'] mb-8 px-4 inline-block bg-black/50 backdrop-blur-sm rounded-lg shadow-xl">
  Only the brave dare tread among the stars.
</p>

  </main>

</body>
</html>
