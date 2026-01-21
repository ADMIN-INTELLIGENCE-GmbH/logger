@extends('layouts.logger')

@section('title', $project->name . ' - Log Explorer')

@section('content')
<div x-data="{ 
    selectedLog: null,
    showModal: false,
    showDeleteConfirm: false,
    showLLMDialog: false,
    llmAnalysis: null,
    llmLoading: false,
    llmError: null,
    selectedLogs: [],
    showBulkDeleteConfirm: false,
    bulkDeleting: false,
    llmFields: {
        channel: true,
        controller: true,
        route: true,
        method: true,
        environment: true,
        context: true,
        extra: false,
        request_url: true,
        user_id: false,
        ip_address: false,
        user_agent: false,
        referrer: false
    },
    get allLogsSelected() {
        const logIds = {{ $logs->pluck('id')->toJson() }};
        return logIds.length > 0 && logIds.every(id => this.selectedLogs.includes(id));
    },
    toggleAllLogs() {
        const logIds = {{ $logs->pluck('id')->toJson() }};
        if (this.allLogsSelected) {
            this.selectedLogs = this.selectedLogs.filter(id => !logIds.includes(id));
        } else {
            logIds.forEach(id => {
                if (!this.selectedLogs.includes(id)) {
                    this.selectedLogs.push(id);
                }
            });
        }
    },
    toggleLogSelection(logId) {
        const index = this.selectedLogs.indexOf(logId);
        if (index === -1) {
            this.selectedLogs.push(logId);
        } else {
            this.selectedLogs.splice(index, 1);
        }
    },
    async bulkDelete() {
        if (this.selectedLogs.length === 0) return;
        
        this.bulkDeleting = true;
        
        try {
            const response = await fetch('/projects/{{ $project->id }}/logs/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    log_ids: this.selectedLogs
                })
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Failed to delete logs');
                this.bulkDeleting = false;
            }
        } catch (error) {
            alert('Failed to connect to the server');
            this.bulkDeleting = false;
        }
    },
    openLog(log) {
        this.selectedLog = log;
        this.showModal = true;
        this.showDeleteConfirm = false;
        this.llmAnalysis = null;
        this.llmError = null;
    },
    openLLMDialog() {
        this.showLLMDialog = true;
        this.llmAnalysis = null;
        this.llmError = null;
    },
    async deleteLog() {
        if (!this.selectedLog) return;
        
        try {
            const response = await fetch(`/projects/{{ $project->id }}/logs/${this.selectedLog.id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                this.showModal = false;
                window.location.reload();
            } else {
                alert('Failed to delete log');
            }
        } catch (error) {
            alert('Failed to connect to the server');
        }
    },
    async askLLM() {
        if (!this.selectedLog) return;
        
        this.showLLMDialog = false;
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
                },
                body: JSON.stringify({
                    fields: this.llmFields
                })
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
    },
    async copyToClipboard(event) {
        if (!this.selectedLog) return;
        
        const log = this.selectedLog;
        let text = '=== LOG DETAILS ===\n\n';
        
        text += 'Level: ' + log.level.toUpperCase() + '\n';
        text += 'Logged At: ' + (log.logged_at || log.created_at) + '\n';
        if (log.logged_at && log.created_at !== log.logged_at) {
            text += 'Received At: ' + log.created_at + '\n';
        }
        text += 'Channel: ' + (log.channel || '-') + '\n';
        text += 'Environment: ' + (log.app_env || '-');
        if (log.app_debug) text += ' (debug)';
        text += '\n';
        text += 'Route: ' + (log.route_name || '-') + '\n';
        text += 'Method: ' + (log.method || '-') + '\n';
        text += 'User ID: ' + (log.user_id || '-') + '\n';
        text += 'IP Address: ' + (log.ip_address || '-') + '\n';
        
        if (log.controller) {
            text += '\nController:\n' + log.controller + '\n';
        }
        
        if (log.user_agent) {
            text += '\nUser Agent:\n' + log.user_agent + '\n';
        }
        
        if (log.request_url) {
            text += '\nRequest URL:\n' + log.request_url + '\n';
        }
        
        if (log.referrer) {
            text += '\nReferrer:\n' + log.referrer + '\n';
        }
        
        text += '\n=== MESSAGE ===\n' + log.message + '\n';
        
        if (log.context) {
            text += '\n=== CONTEXT ===\n' + JSON.stringify(log.context, null, 2) + '\n';
        }
        
        if (log.extra && Object.keys(log.extra).length > 0) {
            text += '\n=== EXTRA (Monolog Data) ===\n' + JSON.stringify(log.extra, null, 2) + '\n';
        }
        
        try {
            await navigator.clipboard.writeText(text);
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.textContent = '✓ Copied!';
            setTimeout(function() {
                btn.innerHTML = originalText;
            }, 2000);
        } catch (error) {
            alert('Failed to copy to clipboard');
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
                    @foreach(\App\Models\Log::LEVELS as $level)
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

            <!-- Method -->
            <div>
                <label for="method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Method</label>
                <input type="text" name="method" id="method" value="{{ request('method') }}" placeholder="Method name..." class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            </div>

            <!-- Filter Buttons -->
            <div class="lg:col-span-5 flex justify-end space-x-3">
                <a href="{{ route('projects.logs.index', $project) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Clear Filters
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    <i class="mdi mdi-magnify mr-2"></i>
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-show="selectedLogs.length > 0" x-cloak class="mb-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    <span x-text="selectedLogs.length"></span> log<span x-show="selectedLogs.length !== 1">s</span> selected
                </span>
                <button @click="selectedLogs = []" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    Clear selection
                </button>
            </div>
            <div class="flex items-center space-x-3">
                <template x-if="!showBulkDeleteConfirm">
                    <button @click="showBulkDeleteConfirm = true" class="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-600 text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <i class="mdi mdi-trash-can mr-2"></i>
                        Delete Selected
                    </button>
                </template>
                <template x-if="showBulkDeleteConfirm">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-red-600 dark:text-red-400 font-medium">Are you sure?</span>
                        <button @click="bulkDelete()" :disabled="bulkDeleting" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!bulkDeleting">Yes, Delete</span>
                            <span x-show="bulkDeleting">Deleting...</span>
                        </button>
                        <button @click="showBulkDeleteConfirm = false" :disabled="bulkDeleting" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                    </div>
                </template>
            </div>
        </div>
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
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" :checked="allLogsSelected" @change="toggleAllLogs()" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                        </th>
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                            <input type="checkbox" :checked="selectedLogs.includes({{ $log->id }})" @change="toggleLogSelection({{ $log->id }})" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ ($log->logged_at ?? $log->created_at)->format('M d, H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($log->level === 'critical') bg-red-900 text-white
                                        @elseif($log->level === 'error') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                        @elseif($log->level === 'info') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                        @endif">
                                    {{ ucfirst($log->level) }}
                                </span>
                                @if($log->channel === 'javascript')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        JS
                                    </span>
                                @endif
                            </div>
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
                            <button @click="openLog({{ $log->toJson() }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="mdi mdi-text-box-search-outline text-5xl text-gray-400 dark:text-gray-500"></i>
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
                            <i class="mdi mdi-close text-2xl"></i>
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
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Logged At:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white" x-text="selectedLog.logged_at || selectedLog.created_at"></span>
                                </div>
                                <div x-show="selectedLog.logged_at && selectedLog.created_at !== selectedLog.logged_at">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Received At:</span>
                                    <span class="ml-2 text-gray-600 dark:text-gray-400 text-sm" x-text="selectedLog.created_at"></span>
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

                            <!-- Controller (full width) -->
                            <div x-show="selectedLog.controller">
                                <span class="font-medium text-gray-500 dark:text-gray-400 text-sm">Controller:</span>
                                <div class="mt-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-md text-sm text-gray-900 dark:text-white break-all font-mono" x-text="selectedLog.controller"></div>
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

                            <!-- JS Stack Trace -->
                            <div x-show="selectedLog.channel === 'javascript' && selectedLog.context && (selectedLog.context.frames || selectedLog.context.stack)">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Stack Trace</h4>
                                <div class="bg-gray-800 dark:bg-gray-900 p-4 rounded-md text-sm overflow-x-auto max-h-96 font-mono">
                                    <template x-if="selectedLog.context.frames">
                                        <div class="space-y-1">
                                            <template x-for="(frame, index) in selectedLog.context.frames" :key="index">
                                                <div class="text-gray-300">
                                                    <span class="text-blue-400" x-text="frame.functionName || 'unknown'"></span>
                                                    <span class="text-gray-500"> at </span>
                                                    <span class="text-green-400" x-text="frame.fileName"></span>
                                                    <span class="text-gray-500">:</span>
                                                    <span class="text-yellow-400" x-text="frame.lineNumber"></span>
                                                    <span class="text-gray-500">:</span>
                                                    <span class="text-yellow-400" x-text="frame.columnNumber"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!selectedLog.context.frames && selectedLog.context.stack">
                                        <pre class="text-gray-300 whitespace-pre-wrap" x-text="selectedLog.context.stack"></pre>
                                    </template>
                                </div>
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
                                        <i class="mdi mdi-alert-circle text-xl text-red-400"></i>
                                        <p class="ml-3 text-sm text-red-700 dark:text-red-300" x-text="llmError"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="llmAnalysis" class="mt-4">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                    <i class="mdi mdi-robot mr-2 text-xl text-indigo-500"></i>
                                    AI Analysis
                                </h4>
                                <div class="ai-analysis-content bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800 rounded-md p-4 prose prose-sm max-w-none overflow-x-auto" x-html="llmAnalysis ? marked.parse(llmAnalysis) : ''"></div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button
                        type="button"
                        @click="openLLMDialog()"
                        class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="mdi mdi-robot mr-2"></i>
                        <span x-show="!llmLoading">Ask LLM</span>
                        <span x-show="llmLoading">Analyzing...</span>
                    </button>
                    <button
                        type="button"
                        @click="copyToClipboard($event)"
                        class="mt-3 w-full inline-flex justify-center items-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        <i class="mdi mdi-content-copy mr-2"></i>
                        Copy to Clipboard
                    </button>
                    <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Close
                    </button>
                    <!-- Delete Button -->
                    <div class="sm:flex-1"></div>
                    <template x-if="!showDeleteConfirm">
                        <button
                            type="button"
                            @click="showDeleteConfirm = true"
                            class="mt-3 w-full inline-flex justify-center items-center rounded-md border border-red-300 dark:border-red-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:w-auto sm:text-sm">
                            <i class="mdi mdi-trash-can mr-2"></i>
                            Delete
                        </button>
                    </template>
                    <template x-if="showDeleteConfirm">
                        <div class="mt-3 sm:mt-0 flex items-center gap-2">
                            <span class="text-sm text-red-600 dark:text-red-400">Are you sure?</span>
                            <button
                                type="button"
                                @click="deleteLog()"
                                class="inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-3 py-1.5 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Yes, Delete
                            </button>
                            <button
                                type="button"
                                @click="showDeleteConfirm = false"
                                class="inline-flex justify-center items-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-3 py-1.5 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- LLM Privacy Dialog -->
    <div x-show="showLLMDialog" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showLLMDialog" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showLLMDialog = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showLLMDialog" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Data to Send to AI</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose which fields to include in the AI analysis. Privacy-sensitive fields are unchecked by default.</p>
                        </div>
                        <button @click="showLLMDialog = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <i class="mdi mdi-close text-2xl"></i>
                        </button>
                    </div>

                    <!-- Always Included -->
                    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-3">Always Included</h4>
                        <div class="space-y-2 text-sm text-blue-800 dark:text-blue-400">
                            <p>• <strong>Log Level</strong> - The severity level (debug, info, warning, error, etc.)</p>
                            <p>• <strong>Log Message</strong> - The main error or log message</p>
                        </div>
                    </div>

                    <!-- Optional Fields -->
                    <div class="space-y-3 mb-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Optional Fields</h4>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.channel" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Channel</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.controller" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Controller</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.route" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Route Name</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.method" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">HTTP Method (GET, POST, etc.)</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.environment" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Environment (production, staging, etc.)</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.context" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Context Data (Stack trace, debugging info)</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.extra" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Extra Monolog Data</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" x-model="llmFields.request_url" class="w-4 h-4 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Request URL</span>
                        </label>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium">⚠️ Privacy-Sensitive (Unchecked by Default)</p>

                            <label class="flex items-center space-x-3 cursor-pointer opacity-75">
                                <input type="checkbox" x-model="llmFields.user_id" class="w-4 h-4 text-red-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-red-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">User ID</span>
                            </label>

                            <label class="flex items-center space-x-3 cursor-pointer opacity-75">
                                <input type="checkbox" x-model="llmFields.ip_address" class="w-4 h-4 text-red-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-red-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">IP Address</span>
                            </label>

                            <label class="flex items-center space-x-3 cursor-pointer opacity-75">
                                <input type="checkbox" x-model="llmFields.user_agent" class="w-4 h-4 text-red-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-red-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">User Agent</span>
                            </label>

                            <label class="flex items-center space-x-3 cursor-pointer opacity-75">
                                <input type="checkbox" x-model="llmFields.referrer" class="w-4 h-4 text-red-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-red-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Referrer</span>
                            </label>
                        </div>
                    </div>

                    <!-- Preview of what will be sent -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-md p-4 mb-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Structure to be Sent</h4>
                        <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-x-auto"><code>{
  "level": "error",
  "message": "...",
  <span x-show="llmFields.channel">"channel": "...",</span>
  <span x-show="llmFields.controller">"controller": "...",</span>
  <span x-show="llmFields.route">"route_name": "...",</span>
  <span x-show="llmFields.method">"method": "...",</span>
  <span x-show="llmFields.environment">"app_env": "...",</span>
  <span x-show="llmFields.context">"context": {...},</span>
  <span x-show="llmFields.extra">"extra": {...},</span>
  <span x-show="llmFields.request_url">"request_url": "...",</span>
  <span x-show="llmFields.user_id">"user_id": "...",</span>
  <span x-show="llmFields.ip_address">"ip_address": "...",</span>
  <span x-show="llmFields.user_agent">"user_agent": "...",</span>
  <span x-show="llmFields.referrer">"referrer": "...",</span>
}</code></pre>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button
                        type="button"
                        @click="askLLM()"
                        :disabled="llmLoading"
                        class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="mdi mdi-robot mr-2"></i>
                        <span x-show="!llmLoading">Send to AI</span>
                        <span x-show="llmLoading">Analyzing...</span>
                    </button>
                    <button type="button" @click="showLLMDialog = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection