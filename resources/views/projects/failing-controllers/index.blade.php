@extends('layouts.logger')

@section('title', $project->name . ' - Failing Controllers')

@section('content')
<div x-data="{ expandedRows: {} }">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Failing Controllers</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $project->name }} - Error hotspots by controller</p>
    </div>

    <!-- Summary Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                <i class="mdi mdi-alert-circle-outline text-2xl text-red-600 dark:text-red-400"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Errors</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalErrors) }}</p>
            </div>
        </div>
    </div>

    <!-- Failing Controllers Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Controllers by Error Count</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Showing controllers with the most errors</p>
        </div>

        @if($failingControllers->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <i class="mdi mdi-check-circle-outline text-5xl text-green-400"></i>
                <p class="mt-4 text-lg font-medium text-green-600 dark:text-green-400">No errors found!</p>
                <p class="mt-2">All controllers are running smoothly.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Controller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error Count</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">% of Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trend</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($failingControllers as $index => $controller)
                            @php
                                $percentage = $totalErrors > 0 ? round(($controller->total / $totalErrors) * 100, 1) : 0;
                                $uniqueId = 'expanded-' . $index;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full 
                                        @if($index === 0) bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                        @elseif($index === 1) bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200
                                        @elseif($index === 2) bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                        @endif text-sm font-medium">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <button @click="expandedRows['{{ $uniqueId }}'] = !expandedRows['{{ $uniqueId }}']" class="mr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
                                            <i x-show="!expandedRows['{{ $uniqueId }}']" class="mdi mdi-chevron-right"></i>
                                            <i x-show="expandedRows['{{ $uniqueId }}']" class="mdi mdi-chevron-down"></i>
                                        </button>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ class_basename($controller->controller) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $controller->controller }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($controller->total) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-2">
                                            <div class="bg-red-500 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $percentage }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <!-- Placeholder for trend indicator -->
                                    <i class="mdi mdi-trending-up text-xl text-gray-400"></i>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('projects.logs.index', ['project' => $project, 'controller' => $controller->controller, 'level' => 'error']) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                        View Logs →
                                    </a>
                                </td>
                            </tr>
                            <!-- Expanded Methods Breakdown -->
                            <tr x-show="expandedRows['{{ $uniqueId }}']" x-transition class="bg-gray-50 dark:bg-gray-900">
                                <td colspan="6" class="px-6 py-4">
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">Method Breakdown ({{ $controller->methods->count() }} methods)</h4>
                                        <div class="space-y-2">
                                            @forelse($controller->methods as $method => $count)
                                                <div class="flex items-center justify-between py-3 px-4 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                                                    <div class="flex items-center space-x-3 flex-1">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                                            {{ $method }}
                                                        </span>
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($count) }} {{ Str::plural('error', $count) }}</span>
                                                    </div>
                                                    <div class="flex items-center space-x-4">
                                                        <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                                            <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ round(($count / $controller->total) * 100) }}%"></div>
                                                        </div>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 text-right">{{ round(($count / $controller->total) * 100) }}%</span>
                                                        <a href="{{ route('projects.logs.index', ['project' => $project, 'controller' => $controller->controller, 'method' => $method, 'level' => 'error']) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium">
                                                            View →
                                                        </a>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No methods found</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

