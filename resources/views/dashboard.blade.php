@extends('layouts.logger')

@section('title', 'Dashboard')

@section('content')
<div>
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Overview of all projects and server status</p>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Projects</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $overallStats['total_projects'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Logs (24h)</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['total_logs_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Errors (24h)</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['total_errors_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 {{ $overallStats['overall_error_rate'] > 10 ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' }} rounded-lg">
                    <svg class="w-6 h-6 {{ $overallStats['overall_error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Rate</h3>
                    <p class="text-2xl font-bold {{ $overallStats['overall_error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $overallStats['overall_error_rate'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">All Projects</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($projects as $item)
            @php
                $project = $item['project'];
                $stats = $project->server_stats ?? [];
                $formatBytes = function($bytes) {
                    if ($bytes < 1024) return $bytes . ' B';
                    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
                    if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
                    return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
                };
            @endphp
            
            <a href="{{ route('projects.dashboard', $project) }}" class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                <div class="p-6">
                    <!-- Project Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->name }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                            {{ $project->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Logs (24h)</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($item['total_logs_24h']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Errors</p>
                            <p class="text-lg font-semibold {{ $item['error_logs_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ number_format($item['error_logs_24h']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Rate</p>
                            <p class="text-lg font-semibold {{ $item['error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $item['error_rate'] }}%</p>
                        </div>
                    </div>

                    <!-- Server Stats -->
                    @if(!empty($stats))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        @if(isset($stats['system']))
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Memory (App):</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ isset($stats['system']['memory_usage']) ? $formatBytes($stats['system']['memory_usage']) : 'N/A' }}</span>
                            </div>
                            @if(isset($stats['system']['server_memory']))
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Memory (Server):</span>
                                <span class="font-medium {{ $stats['system']['server_memory']['percent_used'] > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ round($stats['system']['server_memory']['percent_used']) }}%</span>
                            </div>
                            @endif
                        @endif
                        @if(isset($stats['system']['disk_space']['percent_used']))
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Disk:</span>
                                <span class="font-medium {{ $stats['system']['disk_space']['percent_used'] > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ round($stats['system']['disk_space']['percent_used']) }}%</span>
                            </div>
                        @endif
                        @if(isset($stats['queue']))
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Queue:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $stats['queue']['size'] ?? 0 }} jobs</span>
                            </div>
                        @endif
                        @if($project->last_server_stats_at)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Updated:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $project->last_server_stats_at->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                    @else
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">No server stats available</p>
                    </div>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No active projects found.</p>
            </div>
        @endforelse
        @php $project = null; @endphp
    </div>
</div>
@endsection
