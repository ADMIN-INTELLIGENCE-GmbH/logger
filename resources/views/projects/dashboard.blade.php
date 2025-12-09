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
                    <option value="errors" {{ $stats['level_filter'] === 'errors' ? 'selected' : '' }}>Errors Only</option>
                    <option value="critical" {{ $stats['level_filter'] === 'critical' ? 'selected' : '' }}>Critical Only</option>
                    <option value="error" {{ $stats['level_filter'] === 'error' ? 'selected' : '' }}>Error Only</option>
                    <option value="warning" {{ $stats['level_filter'] === 'warning' ? 'selected' : '' }}>Warning Only</option>
                    <option value="info" {{ $stats['level_filter'] === 'info' ? 'selected' : '' }}>Info Only</option>
                    <option value="debug" {{ $stats['level_filter'] === 'debug' ? 'selected' : '' }}>Debug Only</option>
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

    <!-- Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Logs by {{ $stats['group_format'] === 'hour' ? 'Hour' : 'Day' }} 
            (Last {{ $stats['range'] }})
            @if($stats['level_filter'] !== 'all')
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">- {{ ucfirst($stats['level_filter']) }}</span>
            @endif
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
    const chartData = @json($chartData);
    const groupFormat = @json($stats['group_format']);
    
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
