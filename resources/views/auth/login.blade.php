<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - Adobe Mail Center</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#070B1A] min-h-screen overflow-hidden">

    <!-- Background Glow -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-60 -left-60 w-[700px] h-[700px] rounded-full bg-blue-600/20 blur-[180px]"></div>
        <div class="absolute -bottom-60 -right-60 w-[700px] h-[700px] rounded-full bg-indigo-600/20 blur-[180px]">
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center px-6">

        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-10">
                <div
                    class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center justify-center shadow-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>

                <h1 class="mt-6 text-4xl font-bold text-white">
                    Adobe Mail Center
                </h1>

                <p class="mt-2 text-slate-400">
                    Admin Dashboard
                </p>
            </div>

            <!-- Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">

                @if (session('status'))
                    <div class="mb-5 rounded-xl bg-green-500/10 border border-green-500/30 p-3 text-sm text-green-300">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Email
                        </label>

                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 outline-none"
                            placeholder="Masukkan email">

                        @error('email')
                            <p class="mt-2 text-sm text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mt-5">
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Password
                        </label>

                        <input type="password" name="password" required autocomplete="current-password"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 outline-none"
                            placeholder="Masukkan password">

                        @error('password')
                            <p class="mt-2 text-sm text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center justify-between mt-5">

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember"
                                class="rounded border-white/20 bg-white/5 text-blue-600 focus:ring-blue-500">

                            <span class="text-sm text-slate-400">
                                Remember Me
                            </span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-blue-400 hover:text-blue-300">
                                Forgot Password?
                            </a>
                        @endif

                    </div>

                    <!-- Button -->
                    <button type="submit"
                        class="mt-7 w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-3 font-semibold text-white transition-all duration-300 hover:scale-[1.02] hover:from-blue-700 hover:to-indigo-700 active:scale-100">
                        Login
                    </button>

                </form>

            </div>

            <p class="mt-8 text-center text-sm text-slate-500">
                © {{ date('Y') }} Mandar Runningfest. All rights reserved.
            </p>

        </div>

    </div>

</body>

</html>
