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
                    <svg x-show="!showMagicKey" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showMagicKey" x-cloak class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                    <span x-text="showMagicKey ? 'Hide' : 'Show'"></span>
                </button>
            </div>
            <div x-show="showMagicKey" x-cloak class="mt-3">
                <code class="block bg-gray-800 dark:bg-gray-900 text-green-400 p-3 rounded-md text-sm font-mono break-all">{{ $project->magic_key }}</code>
            </div>
        </div>
    </div>

    <!-- Time Range & Filter Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
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
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
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
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
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
                    <svg class="w-6 h-6 {{ $stats['error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Rate</h3>
                    <p class="text-2xl font-bold {{ $stats['error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $stats['error_rate'] }}%</p>
                </div>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $stats = $project->server_stats;
                $displayCards = [];
                
                // Helper function to format bytes
                $formatBytes = function($bytes) {
                    if ($bytes < 1024) return $bytes . ' B';
                    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
                    if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
                    return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
                };
                
                // Application Info Card
                if (isset($stats['app_name']) || isset($stats['app_env']) || isset($stats['instance_id'])) {
                    $displayCards[] = [
                        'title' => 'Application',
                        'items' => array_filter([
                            'Name' => $stats['app_name'] ?? null,
                            'Environment' => $stats['app_env'] ?? null,
                            'Instance' => $stats['instance_id'] ?? null,
                        ])
                    ];
                }
                
                // System Card
                if (isset($stats['system']) && is_array($stats['system'])) {
                    $displayCards[] = [
                        'title' => 'System',
                        'items' => array_filter([
                            'PHP' => $stats['system']['php_version'] ?? null,
                            'Laravel' => $stats['system']['laravel_version'] ?? null,
                            'Memory (App)' => isset($stats['system']['memory_usage']) ? round($stats['system']['memory_usage'] / 1024 / 1024) . ' MB' : null,
                            'Peak' => isset($stats['system']['memory_peak']) ? round($stats['system']['memory_peak'] / 1024 / 1024) . ' MB' : null,
                        ])
                    ];
                }
                
                // Server Memory Card
                if (isset($stats['system']['server_memory']) && is_array($stats['system']['server_memory'])) {
                    $displayCards[] = [
                        'title' => 'Server Memory',
                        'items' => array_filter([
                            'Used' => isset($stats['system']['server_memory']['used']) ? $formatBytes($stats['system']['server_memory']['used']) : null,
                            'Total' => isset($stats['system']['server_memory']['total']) ? $formatBytes($stats['system']['server_memory']['total']) : null,
                            'Usage' => isset($stats['system']['server_memory']['percent_used']) ? round($stats['system']['server_memory']['percent_used']) . '%' : null,
                        ])
                    ];
                }
                
                // Queue Card
                if (isset($stats['queue']) && is_array($stats['queue'])) {
                    $displayCards[] = [
                        'title' => 'Queue',
                        'items' => array_filter([
                            'Jobs' => $stats['queue']['size'] ?? null,
                            'Connection' => $stats['queue']['connection'] ?? null,
                        ])
                    ];
                }
                
                // Database Card
                if (isset($stats['database']) && is_array($stats['database'])) {
                    $displayCards[] = [
                        'title' => 'Database',
                        'items' => array_filter([
                            'Status' => $stats['database']['status'] ?? null,
                            'Latency' => isset($stats['database']['latency_ms']) ? $stats['database']['latency_ms'] . ' ms' : null,
                        ])
                    ];
                }
                
                // Limit to 4 cards
                $displayCards = array_slice($displayCards, 0, 4);
            @endphp
            
            @foreach($displayCards as $card)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $card['title'] }}</h4>
                    <div class="space-y-2">
                        @foreach($card['items'] as $label => $value)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}:</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        
        @if(isset($stats['system']['server_memory']) && is_array($stats['system']['server_memory']))
        <!-- Server Memory Section -->
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Server Memory</h4>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                @php
                    $memoryData = $stats['system']['server_memory'];
                @endphp
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">Memory Usage</h5>
                    <span class="text-xs font-semibold {{ $memoryData['percent_used'] > 80 ? 'text-red-600 dark:text-red-400' : ($memoryData['percent_used'] > 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}">
                        {{ round($memoryData['percent_used']) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full {{ $memoryData['percent_used'] > 80 ? 'bg-red-500' : ($memoryData['percent_used'] > 60 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($memoryData['percent_used'], 100) }}%"></div>
                </div>
                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex justify-between">
                    <span>Used: {{ $formatBytes($memoryData['used'] ?? 0) }}</span>
                    <span>Total: {{ $formatBytes($memoryData['total'] ?? 0) }}</span>
                </div>
            </div>
        </div>
        @endif
        
        @if(isset($stats['system']['disk_space']) && is_array($stats['system']['disk_space']) && isset($stats['system']['disk_space']['percent_used']))
        <!-- Disk Space Section -->
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Disk Space</h4>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                @php
                    $diskData = $stats['system']['disk_space'];
                @endphp
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">Server Disk</h5>
                    <span class="text-xs font-semibold {{ $diskData['percent_used'] > 80 ? 'text-red-600 dark:text-red-400' : ($diskData['percent_used'] > 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}">
                        {{ round($diskData['percent_used']) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full {{ $diskData['percent_used'] > 80 ? 'bg-red-500' : ($diskData['percent_used'] > 60 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($diskData['percent_used'], 100) }}%"></div>
                </div>
                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex justify-between">
                    <span>Used: {{ $formatBytes($diskData['used'] ?? 0) }}</span>
                    <span>Total: {{ $formatBytes($diskData['total'] ?? 0) }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

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
