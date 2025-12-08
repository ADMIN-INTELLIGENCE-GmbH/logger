@extends('layouts.logger')

@section('title', $project->name . ' - Log Explorer')

@section('content')
<div x-data="{ 
    selectedLog: null,
    showModal: false,
    llmAnalysis: null,
    llmLoading: false,
    llmError: null,
    openLog(log) {
        this.selectedLog = log;
        this.showModal = true;
        this.llmAnalysis = null;
        this.llmError = null;
    },
    async askLLM() {
        if (!this.selectedLog) return;
        
        this.llmLoading = true;
        this.llmError = null;
        this.llmAnalysis = null;
        
        try {
            const response = await fetch(`/projects/{{ $project->id }}/logs/${this.selectedLog.id}/analyze`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.llmAnalysis = data.analysis;
            } else {
                this.llmError = data.error || 'Failed to analyze log';
            }
        } catch (error) {
            this.llmError = 'Failed to connect to the server';
        } finally {
            this.llmLoading = false;
        }
    }
}">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Log Explorer</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $project->name }}</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form method="GET" action="{{ route('projects.logs.index', $project) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Level Filter -->
            <div>
                <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
                <select name="level" id="level" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                    <option value="">All Levels</option>
                    @foreach(['debug', 'info', 'error', 'critical'] as $level)
                        <option value="{{ $level }}" {{ request('level') === $level ? 'selected' : '' }}>
                            {{ ucfirst($level) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Message -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Message</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search..." class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            </div>

            <!-- User ID -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID</label>
                <input type="text" name="user_id" id="user_id" value="{{ request('user_id') }}" placeholder="User ID..." class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            </div>

            <!-- Controller -->
            <div>
                <label for="controller" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Controller</label>
                <input type="text" name="controller" id="controller" value="{{ request('controller') }}" placeholder="Controller class..." class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            </div>

            <!-- Filter Buttons -->
            <div class="lg:col-span-4 flex justify-end space-x-3">
                <a href="{{ route('projects.logs.index', $project) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Clear Filters
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Results Count -->
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} logs
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Controller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" @click="openLog({{ $log->toJson() }})">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->created_at->format('M d, H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($log->level === 'critical') bg-red-900 text-white
                                    @elseif($log->level === 'error') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                    @elseif($log->level === 'info') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($log->level) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate">
                                {{ Str::limit($log->message, 80) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->controller ? class_basename($log->controller) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->user_id ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-4">No logs found matching your criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3 border-t border-gray-200 dark:border-gray-600">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Log Detail Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Log Details</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <template x-if="selectedLog">
                        <div class="space-y-4">
                            <!-- Log Info Grid -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Level:</span>
                                    <span class="ml-2" :class="{
                                        'text-red-600 dark:text-red-400 font-bold': selectedLog.level === 'critical' || selectedLog.level === 'error',
                                        'text-blue-600 dark:text-blue-400': selectedLog.level === 'info',
                                        'text-gray-600 dark:text-gray-300': selectedLog.level === 'debug'
                                    }" x-text="selectedLog.level.toUpperCase()"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Timestamp:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.created_at"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Channel:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.channel || '-'"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Environment:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.app_env || '-'"></span>
                                    <span x-show="selectedLog.app_debug" class="ml-1 text-xs text-yellow-600 dark:text-yellow-400 font-medium">(debug)</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Controller:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.controller || '-'"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Route:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.route_name || '-'"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Method:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.method || '-'"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">User ID:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.user_id || '-'"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">IP Address:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.ip_address || '-'"></span>
                                </div>
                            </div>

                            <!-- User Agent (full width) -->
                            <div x-show="selectedLog.user_agent">
                                <span class="font-medium text-gray-500 dark:text-gray-400 text-sm">User Agent:</span>
                                <div class="mt-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-md text-sm text-gray-900 dark:text-white break-all" x-text="selectedLog.user_agent"></div>
                            </div>

                            <!-- Request URL (full width) -->
                            <div x-show="selectedLog.request_url">
                                <span class="font-medium text-gray-500 dark:text-gray-400 text-sm">Request URL:</span>
                                <div class="mt-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-md text-sm text-gray-900 dark:text-white break-all" x-text="selectedLog.request_url"></div>
                            </div>

                            <!-- Referrer (full width) -->
                            <div x-show="selectedLog.referrer">
                                <span class="font-medium text-gray-500 dark:text-gray-400 text-sm">Referrer:</span>
                                <div class="mt-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-md text-sm text-gray-900 dark:text-white break-all" x-text="selectedLog.referrer"></div>
                            </div>

                            <!-- Message -->
                            <div>
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Message</h4>
                                <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-md text-sm text-gray-900 dark:text-white" x-text="selectedLog.message"></div>
                            </div>

                            <!-- Context -->
                            <div x-show="selectedLog.context">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Context</h4>
                                <pre class="bg-gray-800 dark:bg-gray-900 text-green-400 p-4 rounded-md text-sm overflow-x-auto max-h-96"><code x-text="JSON.stringify(selectedLog.context, null, 2)"></code></pre>
                            </div>

                            <!-- Extra (Monolog processor data) -->
                            <div x-show="selectedLog.extra && Object.keys(selectedLog.extra).length > 0">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Extra (Monolog Data)</h4>
                                <pre class="bg-gray-800 dark:bg-gray-900 text-blue-400 p-4 rounded-md text-sm overflow-x-auto max-h-48"><code x-text="JSON.stringify(selectedLog.extra, null, 2)"></code></pre>
                            </div>

                            <!-- LLM Analysis Section -->
                            <div x-show="llmLoading" class="mt-4">
                                <div class="flex items-center justify-center py-8">
                                    <div class="ai-spinner">
                                        <div class="ai-spinner-inner"></div>
                                    </div>
                                </div>
                            </div>

                            <div x-show="llmError" class="mt-4">
                                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="ml-3 text-sm text-red-700 dark:text-red-300" x-text="llmError"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="llmAnalysis" class="mt-4">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    AI Analysis
                                </h4>
                                <div class="ai-analysis-content bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800 rounded-md p-4 prose prose-sm max-w-none overflow-x-auto" x-html="marked.parse(llmAnalysis)"></div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button 
                        type="button" 
                        @click="askLLM()" 
                        :disabled="llmLoading"
                        class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <span x-show="!llmLoading">Ask LLM</span>
                        <span x-show="llmLoading">Analyzing...</span>
                    </button>
                    <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
