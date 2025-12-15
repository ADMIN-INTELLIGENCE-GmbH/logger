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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $project->is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

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
                
                // Application Info Card
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <!-- Application Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Application</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Name</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['app_name'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Env</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['app_env'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Instance</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white text-xs truncate" title="{{ $serverStats['instance_id'] ?? '-' }}">{{ $serverStats['instance_id'] ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- System Versions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">System Versions</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">PHP</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['system']['php_version'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Laravel</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['system']['laravel_version'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Node.js</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['system']['node_version'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">npm</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['system']['npm_version'] ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Memory Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Memory & CPU</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">App Memory</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ isset($serverStats['system']['memory_usage']) ? $formatBytes($serverStats['system']['memory_usage']) : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Peak Memory</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ isset($serverStats['system']['memory_peak']) ? $formatBytes($serverStats['system']['memory_peak']) : '-' }}</span>
                    </div>
                    @if(isset($serverStats['system']['server_memory']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Server Memory</span>
                        <span class="text-sm font-medium {{ ($serverStats['system']['server_memory']['percent_used'] ?? 0) > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ round($serverStats['system']['server_memory']['percent_used'] ?? 0, 1) }}%</span>
                    </div>
                    @endif
                    @if(isset($serverStats['system']['cpu_usage']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">CPU Usage</span>
                        <span class="text-sm font-medium {{ ($serverStats['system']['cpu_usage'] ?? 0) > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ round($serverStats['system']['cpu_usage'] ?? 0, 1) }}%</span>
                    </div>
                    @endif
                    @if(isset($serverStats['system']['uptime']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Uptime</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ gmdate("H:i:s", $serverStats['system']['uptime']) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Queue & Database -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Queue & DB</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Queue Size</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['queue']['size'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Connection</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $serverStats['queue']['connection'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">DB Status</span>
                        <span class="text-sm font-medium {{ ($serverStats['database']['status'] ?? '') === 'connected' ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">{{ ucfirst($serverStats['database']['status'] ?? '-') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Latency</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ isset($serverStats['database']['latency_ms']) ? $serverStats['database']['latency_ms'] . 'ms' : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Package Security -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Security & Updates</h4>
                <div class="space-y-2">
                    @if(isset($serverStats['system']['composer_outdated']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Composer Outdated</span>
                        <span class="text-sm font-medium {{ $serverStats['system']['composer_outdated'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">{{ $serverStats['system']['composer_outdated'] }}</span>
                    </div>
                    @endif
                    @if(isset($serverStats['system']['npm_outdated']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">npm Outdated</span>
                        <span class="text-sm font-medium {{ $serverStats['system']['npm_outdated'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">{{ $serverStats['system']['npm_outdated'] }}</span>
                    </div>
                    @endif
                    @if(isset($serverStats['system']['composer_audit']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Composer Vulnerabilities</span>
                        <span class="text-sm font-medium {{ $serverStats['system']['composer_audit'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $serverStats['system']['composer_audit'] }}</span>
                    </div>
                    @endif
                    @if(isset($serverStats['system']['npm_audit']))
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">npm Vulnerabilities</span>
                        <span class="text-sm font-medium {{ $serverStats['system']['npm_audit'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $serverStats['system']['npm_audit'] }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- File & Folder Sizes -->
            @if((isset($serverStats['filesize']) && is_array($serverStats['filesize'])) || (isset($serverStats['foldersize']) && is_array($serverStats['foldersize'])))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">File & Folder Sizes</h4>
                <div class="space-y-2">
                    @if(isset($serverStats['filesize']) && is_array($serverStats['filesize']))
                        @foreach($serverStats['filesize'] as $file => $size)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400"><i class="mdi mdi-file-document mr-1"></i>{{ $file }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $formatBytes($size) }}</span>
                            </div>
                        @endforeach
                    @endif
                    @if(isset($serverStats['foldersize']) && is_array($serverStats['foldersize']))
                        @foreach($serverStats['foldersize'] as $folder => $size)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400"><i class="mdi mdi-folder mr-1"></i>{{ $folder }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $formatBytes($size) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Resource Usage Bars -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if(isset($serverStats['system']['cpu_usage']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">CPU Usage</h4>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ round($serverStats['system']['cpu_usage'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-2">
                    <div class="{{ $getUsageColor($serverStats['system']['cpu_usage'] ?? 0) }} h-2.5 rounded-full" style="width: {{ min($serverStats['system']['cpu_usage'] ?? 0, 100) }}%"></div>
                </div>
            </div>
            @endif
            
            @if(isset($serverStats['system']['server_memory']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Server Memory</h4>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ round($serverStats['system']['server_memory']['percent_used'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-4">
                    <div class="{{ $getUsageColor($serverStats['system']['server_memory']['percent_used'] ?? 0) }} h-2.5 rounded-full" style="width: {{ $serverStats['system']['server_memory']['percent_used'] ?? 0 }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Used: {{ $formatBytes($serverStats['system']['server_memory']['used'] ?? 0) }}</span>
                    <span>Total: {{ $formatBytes($serverStats['system']['server_memory']['total'] ?? 0) }}</span>
                </div>
            </div>
            @endif

            @if(isset($serverStats['system']['disk_space']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Disk Space</h4>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ round($serverStats['system']['disk_space']['percent_used'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-4">
                    <div class="{{ $getUsageColor($serverStats['system']['disk_space']['percent_used'] ?? 0) }} h-2.5 rounded-full" style="width: {{ $serverStats['system']['disk_space']['percent_used'] ?? 0 }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Used: {{ $formatBytes($serverStats['system']['disk_space']['used'] ?? 0) }}</span>
                    <span>Total: {{ $formatBytes($serverStats['system']['disk_space']['total'] ?? 0) }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Time Range & Filter Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-8">
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
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

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('projects.logs.index', $project) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800 transition-colors">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
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
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Failing Controllers</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">View error hotspots</p>
                </div>
            </div>
        </a>

        <a href="{{ route('projects.settings.show', $project) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg group-hover:bg-gray-200 dark:group-hover:bg-gray-600 transition-colors">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Settings</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Configure project</p>
                </div>
            </div>
        </a>
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
</script>
@endpush
