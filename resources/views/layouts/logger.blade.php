<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <title>{{ config('app.name', 'Laravel') }}@hasSection('title') - @yield('title')@endif</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Marked.js for Markdown rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <!-- Logo -->
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <img x-show="!darkMode" src="/admin-intelligence-black.svg" alt="Admin Intelligence" class="h-8 w-auto">
                            <img x-show="darkMode" src="/admin-intelligence-white.svg" alt="Admin Intelligence" class="h-8 w-auto">
                            <span class="font-semibold text-xl text-gray-800 dark:text-white">Logger</span>
                        </a>

                        <!-- Main Navigation -->
                        <div class="hidden md:flex items-center space-x-4 text-sm">
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Dashboard</a>
                            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Projects</a>
                            
                            @if(Auth::user()->isAdmin())
                                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Users</a>
                            @endif

                            <!-- Project-specific Navigation -->
                            @if(isset($project) && $project)
                                <span class="text-gray-400 dark:text-gray-600">|</span>
                                <a href="{{ route('projects.dashboard', $project) }}" class="{{ request()->routeIs('projects.dashboard') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Server Dashboard</a>
                                <a href="{{ route('projects.logs.index', $project) }}" class="{{ request()->routeIs('projects.logs.*') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Log Explorer</a>
                                <a href="{{ route('projects.failing-controllers.index', $project) }}" class="{{ request()->routeIs('projects.failing-controllers.*') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Failing Controllers</a>
                                @can('update', $project)
                                    <a href="{{ route('projects.settings.show', $project) }}" class="{{ request()->routeIs('projects.settings.*') ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">Settings</a>
                                @endcan
                            @endif
                        </div>
                    </div>

                    <!-- Right Side: Dark Mode Toggle + User Menu -->
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button @click="darkMode = !darkMode" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none">
                            <i x-show="!darkMode" class="mdi mdi-weather-night text-xl"></i>
                            <i x-show="darkMode" class="mdi mdi-weather-sunny text-xl"></i>
                        </button>

                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white focus:outline-none">
                                <span>{{ Auth::user()->name }}</span>
                                <i class="mdi mdi-chevron-down"></i>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 dark:ring-gray-700">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Page Content -->
        <main class="py-8 flex-grow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-700 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-gray-500 dark:text-gray-400 text-sm">powered by <a href="https://admin-intelligence.de" target="_blank" rel="noopener noreferrer" class="font-semibold underline">ADMIN INTELLIGENCE</a></p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
