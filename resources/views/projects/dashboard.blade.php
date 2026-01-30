@extends('layouts.logger')

@section('title', $project->name . ' - Dashboard')

@section('content')
<div x-data="{ showMagicKey: false }">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $project->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Project Dashboard</p>
            </div>
            <div class="flex items-center space-x-3">
                <div x-data="autoRefresh()" class="flex items-center gap-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Auto-refresh</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-show="enabled" x-text="'Next: ' + countdown + 's'"></span>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $project->is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        @can('update', $project)
            <!-- Magic Key Section -->
            <div class="mt-4 bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Project Key</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use this key in the X-Project-Key header</p>
                    </div>
                    <button @click="showMagicKey = !showMagicKey" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <i x-show="!showMagicKey" class="mdi mdi-eye mr-2"></i>
                        <i x-show="showMagicKey" x-cloak class="mdi mdi-eye-off mr-2"></i>
                        <span x-text="showMagicKey ? 'Hide' : 'Show'"></span>
                    </button>
                </div>
                <div x-show="showMagicKey" x-cloak class="mt-3">
                    <code class="block bg-gray-800 dark:bg-gray-900 text-green-400 p-3 rounded-md text-sm font-mono break-all">{{ $project->magic_key }}</code>
                </div>
            </div>
        @endcan
    </div>

    @if($project->server_stats)
    <!-- Server Status -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Server Status</h3>
            @if($project->last_server_stats_at)
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    @if($project->last_server_stats_at->diffInHours() > 24)
                        Last updated: {{ $project->last_server_stats_at->format('M d, Y H:i') }}
                    @else
                        Last updated: {{ $project->last_server_stats_at->diffForHumans() }}
                    @endif
                </span>
            @endif
        </div>
        
        @php
            $serverStats = $project->server_stats;
            // Helper function to format bytes
                $formatBytes = function($bytes) {
                    if ($bytes < 0) return '<span class="text-red-600 dark:text-red-400">Error</span>';
                    if ($bytes == 0) return '0 B';
                    if ($bytes < 1024) return $bytes . ' B';
                    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
                    if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
                    return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
                };

                // Helper function for usage color
                $getUsageColor = function($percent) {
                    if ($percent >= 90) return 'bg-red-600';
                    if ($percent >= 75) return 'bg-yellow-500';
                    return 'bg-green-500';
                };
                
                $getUsageTextColor = function($percent) {
                    if ($percent >= 90) return 'text-red-600 dark:text-red-400';
                    if ($percent >= 75) return 'text-yellow-600 dark:text-yellow-400';
                    return 'text-green-600 dark:text-green-400';
                };

                $formatUptime = function($seconds) {
                    if ($seconds < 0) return '-';
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    return $hours . 'h ' . $minutes . 'm';
                };
        @endphp

        <!-- Row 1: Critical Health (Heartbeat) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Server Health (CPU + Memory) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Server Health</h4>
                <div class="space-y-4">
                    <!-- CPU -->
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">CPU</span>
                            <span class="text-sm font-bold {{ $getUsageTextColor($serverStats['system']['cpu_usage'] ?? 0) }}">
                                {{ isset($serverStats['system']['cpu_usage']) ? round($serverStats['system']['cpu_usage'], 1) . '%' : '-' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="{{ $getUsageColor($serverStats['system']['cpu_usage'] ?? 0) }} h-2 rounded-full transition-all" 
                                 style="width: {{ min($serverStats['system']['cpu_usage'] ?? 0, 100) }}%"></div>
                        </div>
                    </div>
                    <!-- Memory -->
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Memory</span>
                            <span class="text-sm font-bold {{ $getUsageTextColor($serverStats['system']['server_memory']['percent_used'] ?? 0) }}">
                                {{ isset($serverStats['system']['server_memory']['percent_used']) ? round($serverStats['system']['server_memory']['percent_used'], 1) . '%' : '-' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="{{ $getUsageColor($serverStats['system']['server_memory']['percent_used'] ?? 0) }} h-2 rounded-full transition-all" 
                                 style="width: {{ min($serverStats['system']['server_memory']['percent_used'] ?? 0, 100) }}%"></div>
                        </div>
                        @if(isset($serverStats['system']['server_memory']['used']))
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Used: {{ $formatBytes($serverStats['system']['server_memory']['used']) }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Disk Storage -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Disk Storage</h4>
                @if(isset($serverStats['system']['disk_space']))
                <div class="flex items-center justify-center">
                    <div class="relative w-32 h-32">
                        <!-- SVG Donut Chart -->
                        <svg class="transform -rotate-90 w-32 h-32">
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="12" fill="transparent" class="text-gray-200 dark:text-gray-700" />
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="12" fill="transparent" 
                                    stroke-dasharray="{{ 2 * 3.14159 * 56 }}" 
                                    stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - ($serverStats['system']['disk_space']['percent_used'] ?? 0) / 100) }}" 
                                    class="{{ $getUsageTextColor($serverStats['system']['disk_space']['percent_used'] ?? 0) }}" />
                        </svg>
                        <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ round($serverStats['system']['disk_space']['percent_used'] ?? 0, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ $formatBytes($serverStats['system']['disk_space']['free'] ?? 0) }} Free
                </div>
                @else
                <div class="text-center text-gray-500 dark:text-gray-400">No data</div>
                @endif
            </div>

            <!-- Database -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Database</h4>
                <div class="flex flex-col items-center justify-center h-24">
                    @if(isset($serverStats['database']['status']))
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-3 h-3 rounded-full {{ $serverStats['database']['status'] === 'connected' ? 'bg-green-500' : 'bg-red-500' }} animate-pulse"></div>
                        <span class="text-lg font-bold {{ $serverStats['database']['status'] === 'connected' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ ucfirst($serverStats['database']['status']) }}
                        </span>
                    </div>
                    @if(isset($serverStats['database']['latency_ms']))
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Latency: <span class="font-medium text-gray-900 dark:text-white">{{ round($serverStats['database']['latency_ms'], 2) }}ms</span>
                    </div>
                    @endif
                    @else
                    <div class="text-gray-500 dark:text-gray-400">No data</div>
                    @endif
                </div>
            </div>

            <!-- Uptime -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Uptime</h4>
                <div class="flex flex-col items-center justify-center h-24">
                    @if(isset($serverStats['system']['uptime']))
                    <div class="flex items-center space-x-2 mb-2">
                        <i class="mdi mdi-clock-outline text-2xl text-indigo-600 dark:text-indigo-400"></i>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $formatUptime($serverStats['system']['uptime']) }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($serverStats['system']['uptime']) }} seconds
                    </div>
                    @else
                    <div class="text-gray-500 dark:text-gray-400">No data</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Row 2: Application Performance & State -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Queue Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Queue Status</h4>
                <div class="flex flex-col items-center justify-center">
                    <div class="text-4xl font-bold {{ isset($serverStats['queue']['size']) && $serverStats['queue']['size'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $serverStats['queue']['size'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        {{ isset($serverStats['queue']['size']) && $serverStats['queue']['size'] == 1 ? 'Job Waiting' : 'Jobs Waiting' }}
                    </div>
                    @if(isset($serverStats['queue']['connection']))
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $serverStats['queue']['connection'] }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- App Memory -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">App Memory</h4>
                <div class="flex flex-col items-center justify-center">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ isset($serverStats['system']['memory_usage']) ? $formatBytes($serverStats['system']['memory_usage']) : '-' }}
                    </div>
                    @if(isset($serverStats['system']['memory_peak']))
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        Peak: <span class="font-medium">{{ $formatBytes($serverStats['system']['memory_peak']) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- App Mode (Environment + Debug) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">App Mode</h4>
                <div class="flex flex-col items-center justify-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white uppercase">
                        {{ $serverStats['app_env'] ?? '-' }}
                    </div>
                    @if(isset($serverStats['app_debug']))
                    <div class="mt-3">
                        @if($serverStats['app_debug'])
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 border border-yellow-300 dark:border-yellow-700">
                            <i class="mdi mdi-alert-circle-outline mr-1"></i>
                            Debug Mode
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                            <i class="mdi mdi-check-circle-outline mr-1"></i>
                            Production
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- System Info (Stack Versions) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Stack</h4>
                <div class="flex flex-wrap gap-2 justify-center">
                    @if(isset($serverStats['system']['laravel_version']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                        Laravel {{ $serverStats['system']['laravel_version'] }}
                    </span>
                    @endif
                    @if(isset($serverStats['system']['php_version']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400">
                        PHP {{ $serverStats['system']['php_version'] }}
                    </span>
                    @endif
                    @if(isset($serverStats['system']['node_version']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                        Node {{ $serverStats['system']['node_version'] }}
                    </span>
                    @endif
                    @if(isset($serverStats['log_shipper_version']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400">
                        Shipper {{ $serverStats['log_shipper_version'] }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Row 3: Maintenance & Security (Package Health) -->
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Package Health</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Composer -->
                    <div class="flex items-center justify-between p-6 rounded-lg {{ (isset($serverStats['system']['composer_audit']) && $serverStats['system']['composer_audit'] > 0) ? 'bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800' : 'bg-white dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600' }}">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">Composer</div>
                            <div class="flex items-center space-x-4">
                                <div>
                                    <span class="text-2xl font-bold {{ (isset($serverStats['system']['composer_audit']) && $serverStats['system']['composer_audit'] > 0) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $serverStats['system']['composer_audit'] ?? 0 }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">Vulnerabilities</span>
                                </div>
                                <div class="text-gray-300 dark:text-gray-700">|</div>
                                <div>
                                    <span class="text-2xl font-bold {{ (isset($serverStats['system']['composer_outdated']) && $serverStats['system']['composer_outdated'] > 0) ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $serverStats['system']['composer_outdated'] ?? 0 }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">Outdated</span>
                                </div>
                            </div>
                        </div>
                        <i class="mdi mdi-package-variant text-4xl {{ (isset($serverStats['system']['composer_audit']) && $serverStats['system']['composer_audit'] > 0) ? 'text-red-400 dark:text-red-600' : 'text-gray-400 dark:text-gray-600' }}"></i>
                    </div>

                    <!-- NPM -->
                    <div class="flex items-center justify-between p-6 rounded-lg {{ (isset($serverStats['system']['npm_audit']) && $serverStats['system']['npm_audit'] > 0) ? 'bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800' : 'bg-white dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600' }}">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">NPM</div>
                            <div class="flex items-center space-x-4">
                                <div>
                                    <span class="text-2xl font-bold {{ (isset($serverStats['system']['npm_audit']) && $serverStats['system']['npm_audit'] > 0) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $serverStats['system']['npm_audit'] ?? 0 }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">Vulnerabilities</span>
                                </div>
                                <div class="text-gray-300 dark:text-gray-700">|</div>
                                <div>
                                    <span class="text-2xl font-bold {{ (isset($serverStats['system']['npm_outdated']) && $serverStats['system']['npm_outdated'] > 0) ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $serverStats['system']['npm_outdated'] ?? 0 }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">Outdated</span>
                                </div>
                            </div>
                        </div>
                        <i class="mdi mdi-npm text-4xl {{ (isset($serverStats['system']['npm_audit']) && $serverStats['system']['npm_audit'] > 0) ? 'text-red-400 dark:text-red-600' : 'text-gray-400 dark:text-gray-600' }}"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 4: Dynamic Content (Files & Folders) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" style="grid-auto-rows: 1fr;">
            <!-- Folder Sizes (Scrollable List) -->
            @if(isset($serverStats['foldersize']) && is_array($serverStats['foldersize']) && count($serverStats['foldersize']) > 0)
            @php
                $maxSize = max(array_filter($serverStats['foldersize'], fn($s) => $s > 0));
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Storage Distribution</h4>
                </div>
                <div class="p-6 overflow-y-auto flex-1" style="max-height: 400px;">
                    <div class="space-y-3">
                        @foreach(collect($serverStats['foldersize'])->sortDesc() as $folder => $size)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $folder }}">
                                    <i class="mdi mdi-folder text-yellow-600 dark:text-yellow-400 mr-1"></i>
                                    {{ $folder }}
                                </span>
                                <span class="text-sm font-medium {{ $size < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                    {!! $size < 0 ? 'Error' : $formatBytes($size) !!}
                                </span>
                            </div>
                            @if($size > 0 && $maxSize > 0)
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all" 
                                     style="width: {{ ($size / $maxSize) * 100 }}%"></div>
                            </div>
                            @elseif($size < 0)
                            <div class="text-xs text-red-600 dark:text-red-400">Access Denied / Unknown</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 mt-auto">
                    Total watched folders: {{ count($serverStats['foldersize']) }}
                </div>
            </div>
            @endif

            <!-- Monitored Files (Data Table) -->
            @if(isset($serverStats['filesize']) && is_array($serverStats['filesize']) && count($serverStats['filesize']) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Critical Files</h4>
                </div>
                <div class="overflow-y-auto flex-1" style="max-height: 400px;">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Filename</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(collect($serverStats['filesize'])->sortDesc() as $file => $size)
                            <tr class="{{ $size > (1024 * 1024 * 1024) ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white truncate" title="{{ $file }}">
                                    <i class="mdi mdi-file-document text-blue-600 dark:text-blue-400 mr-1"></i>
                                    {{ $file }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-medium {{ $size == 0 ? 'text-gray-500 dark:text-gray-400' : ($size > (1024 * 1024 * 1024) ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white') }}">
                                    {!! $size == 0 ? 'Empty' : $formatBytes($size) !!}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($size > (1024 * 1024 * 1024))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                        <i class="mdi mdi-alert-circle-outline mr-1"></i>Large
                                    </span>
                                    @elseif($size == 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                        Empty
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                        <i class="mdi mdi-check-circle-outline mr-1"></i>OK
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 mt-auto">
                    Total monitored files: {{ count($serverStats['filesize']) }}
                </div>
            </div>
            @endif
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <i class="mdi mdi-information mr-1"></i>
                Server stats are not yet available. To unlock detailed server health, queue, and security insights, enable and configure the <code>status</code> section in your <strong>config/log-shipper.php</strong> file. See options for metrics, intervals, and monitored paths to tailor the stats to your needs.
            </p>
        </div>
    @endif

    <!-- Time Range & Filter Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <form method="GET" action="{{ route('projects.dashboard', $project) }}" class="flex flex-wrap items-center gap-4">
            <!-- Time Range -->

            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Time Range:</label>
                <select name="range" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                    <option value="1h" {{ $stats['range'] === '1h' ? 'selected' : '' }}>Last 1 hour</option>
                    <option value="6h" {{ $stats['range'] === '6h' ? 'selected' : '' }}>Last 6 hours</option>
                    <option value="24h" {{ $stats['range'] === '24h' ? 'selected' : '' }}>Last 24 hours</option>
                    <option value="7d" {{ $stats['range'] === '7d' ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30d" {{ $stats['range'] === '30d' ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90d" {{ $stats['range'] === '90d' ? 'selected' : '' }}>Last 90 days</option>
                </select>
            </div>

            <!-- Level Filter -->
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show:</label>
                <select name="level" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                    <option value="all" {{ $stats['level_filter'] === 'all' ? 'selected' : '' }}>All Levels</option>
                    <option value="errors" {{ $stats['level_filter'] === 'errors' ? 'selected' : '' }}>Errors & Above</option>
                    <option value="emergency" {{ $stats['level_filter'] === 'emergency' ? 'selected' : '' }}>Emergency</option>
                    <option value="alert" {{ $stats['level_filter'] === 'alert' ? 'selected' : '' }}>Alert</option>
                    <option value="critical" {{ $stats['level_filter'] === 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="error" {{ $stats['level_filter'] === 'error' ? 'selected' : '' }}>Error</option>
                    <option value="warning" {{ $stats['level_filter'] === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="notice" {{ $stats['level_filter'] === 'notice' ? 'selected' : '' }}>Notice</option>
                    <option value="info" {{ $stats['level_filter'] === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="debug" {{ $stats['level_filter'] === 'debug' ? 'selected' : '' }}>Debug</option>
                </select>
            </div>

            <a href="{{ route('projects.dashboard', $project) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                Reset
            </a>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="mdi mdi-file-document-multiple text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Logs ({{ $stats['range'] }})</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_logs']) }}</p>
                </div>
            </div>
        </div>

        <!-- Error Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="mdi mdi-alert-circle text-2xl text-red-600 dark:text-red-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Errors ({{ $stats['range'] }})</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['error_logs']) }}</p>
                </div>
            </div>
        </div>

        <!-- Error Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 {{ $stats['error_rate'] > 10 ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' }} rounded-lg">
                    <i class="mdi mdi-chart-bar text-2xl {{ $stats['error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Rate</h3>
                    <p class="text-2xl font-bold {{ $stats['error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $stats['error_rate'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    @if(isset($chartData) && count($chartData) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            @isset($stats)
                Logs by {{ ($stats['group_format'] ?? 'day') === 'hour' ? 'Hour' : 'Day' }} 
                (Last {{ $stats['range'] ?? '24h' }})
                @if(($stats['level_filter'] ?? 'all') !== 'all')
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">- {{ ucfirst($stats['level_filter']) }}</span>
                @endif
            @else
                Logs Over Time
            @endisset
        </h3>
        <div class="h-64">
            <canvas id="logsChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('projects.logs.index', $project) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800 transition-colors">
                    <i class="mdi mdi-magnify text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Log Explorer</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Search and filter logs</p>
                </div>
            </div>
        </a>

        <a href="{{ route('projects.failing-controllers.index', $project) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900 rounded-lg group-hover:bg-red-200 dark:group-hover:bg-red-800 transition-colors">
                    <i class="mdi mdi-alert-circle-outline text-2xl text-red-600 dark:text-red-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Failing Controllers</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">View error hotspots</p>
                </div>
            </div>
        </a>
        @can('update', $project)
            <a href="{{ route('projects.settings.show', $project) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow group">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg group-hover:bg-gray-200 dark:group-hover:bg-gray-600 transition-colors">
                        <i class="mdi mdi-cog text-2xl text-gray-600 dark:text-gray-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Settings</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Configure project</p>
                    </div>
                </div>
            </a>
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('logsChart').getContext('2d');
    const chartData = @json($chartData ?? []);
    const groupFormat = @json($stats['group_format'] ?? 'day');
    
    // Only render chart if we have data
    if (!chartData || chartData.length === 0) {
        return;
    }
    
    const labels = chartData.map(d => {
        const date = new Date(d.period);
        if (groupFormat === 'hour') {
            return date.getHours() + ':00';
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Info',
                    data: chartData.map(d => d.info || 0),
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    stack: 'stack0',
                },
                {
                    label: 'Debug',
                    data: chartData.map(d => d.debug || 0),
                    backgroundColor: 'rgba(156, 163, 175, 0.8)',
                    stack: 'stack0',
                },
                {
                    label: 'Warning',
                    data: chartData.map(d => d.warning || 0),
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    stack: 'stack0',
                },
                {
                    label: 'Error',
                    data: chartData.map(d => d.error || 0),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    stack: 'stack0',
                },
                {
                    label: 'Critical',
                    data: chartData.map(d => d.critical || 0),
                    backgroundColor: 'rgba(127, 29, 29, 0.8)',
                    stack: 'stack0',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});

// Auto-refresh functionality
document.addEventListener('alpine:init', () => {
    Alpine.data('autoRefresh', () => ({
        enabled: localStorage.getItem('project_dashboard_auto_refresh') !== 'false', // Default to true
        interval: 30, // seconds
        countdown: 30,
        timer: null,
        countdownTimer: null,
        
        init() {
            this.$watch('enabled', value => {
                localStorage.setItem('project_dashboard_auto_refresh', value);
                if (value) {
                    this.start();
                } else {
                    this.stop();
                }
            });
            
            if (this.enabled) {
                this.start();
            }
        },
        
        start() {
            this.countdown = this.interval;
            this.timer = setInterval(() => {
                window.location.reload();
            }, this.interval * 1000);
            
            this.countdownTimer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.countdown = this.interval;
                }
            }, 1000);
        },
        
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
        }
    }));
});
</script>
@endpush
