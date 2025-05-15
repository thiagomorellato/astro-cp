@extends('layouts.app')

@section('content')
    <!-- Login box -->
    <div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl max-w-sm mx-auto shadow-lg border border-white/20">
      <h2 class="text-2xl font-semibold mb-6 text-yellow-400 font-['Cinzel']">Login</h2>

      <form method="POST" action="{{ route('rathena.login') }}">
       @csrf
       <input type="text" name="userid" placeholder="Username" class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" />
       <input type="password" name="password" placeholder="Password" class="w-full mb-4 p-2 rounded bg-white/20 placeholder-white text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" />
       <button type="submit" class="w-full bg-gray-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded transition">Log In</button>
      </form>

      @if ($errors->any())
       <div class="mt-4 text-red-400">
         @foreach ($errors->all() as $error)
           <p>{{ $error }}</p>
         @endforeach
       </div>
      @endif

      <p class="mt-4 text-sm text-white/80">
        New here? <a href="#" class="underline hover:text-yellow-400">Create Account</a>
      </p>
    </div>
@endsection
