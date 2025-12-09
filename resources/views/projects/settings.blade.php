@extends('layouts.logger')

@section('title', $project->name . ' - Settings')

@section('content')
<div x-data="{ showRegenerateConfirm: false, showDeleteConfirm: false, showTruncateConfirm: false, showRegenerateWebhookSecretConfirm: false, showWebhookSecret: false }">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Project Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $project->name }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Settings Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- General Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">General Settings</h3>
                </div>
                <form action="{{ route('projects.settings.update', $project) }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Project Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Retention Policy -->
                    <div>
                        <label for="retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Retention Policy</label>
                        <select name="retention_days" id="retention_days" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                            <option value="7" {{ $project->retention_days == 7 ? 'selected' : '' }}>7 days</option>
                            <option value="14" {{ $project->retention_days == 14 ? 'selected' : '' }}>14 days</option>
                            <option value="30" {{ $project->retention_days == 30 ? 'selected' : '' }}>30 days</option>
                            <option value="90" {{ $project->retention_days == 90 ? 'selected' : '' }}>90 days</option>
                            <option value="-1" {{ $project->retention_days == -1 ? 'selected' : '' }}>Forever</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Logs older than this will be automatically deleted</p>
                        @error('retention_days')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Webhook URL -->
                    <div>
                        <label for="webhook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook URL</label>
                        <input type="url" name="webhook_url" id="webhook_url" value="{{ old('webhook_url', $project->webhook_url) }}" placeholder="https://hooks.slack.com/..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Slack, Discord, Mattermost, or any HTTP endpoint that accepts JSON</p>
                        @error('webhook_url')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Webhook Enabled -->
                    <div class="flex items-center">
                        <input type="hidden" name="webhook_enabled" value="0">
                        <input type="checkbox" name="webhook_enabled" id="webhook_enabled" value="1" {{ $project->webhook_enabled ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded">
                        <label for="webhook_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">Enable webhook notifications</label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 -mt-4 ml-6">When disabled, no webhooks will be sent even if a URL is configured</p>

                    <!-- Webhook Threshold -->
                    <div>
                        <label for="webhook_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notification Threshold</label>
                        <select name="webhook_threshold" id="webhook_threshold" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                            <option value="debug" {{ $project->webhook_threshold == 'debug' ? 'selected' : '' }}>Debug (all logs)</option>
                            <option value="info" {{ $project->webhook_threshold == 'info' ? 'selected' : '' }}>Info and above</option>
                            <option value="error" {{ $project->webhook_threshold == 'error' ? 'selected' : '' }}>Error and above</option>
                            <option value="critical" {{ $project->webhook_threshold == 'critical' ? 'selected' : '' }}>Critical only</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Only logs at or above this level will trigger webhook notifications</p>
                        @error('webhook_threshold')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Webhook Format -->
                    <div>
                        <label for="webhook_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook Format</label>
                        <select name="webhook_format" id="webhook_format" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                            @foreach(App\Models\Project::WEBHOOK_FORMATS as $value => $label)
                                <option value="{{ $value }}" {{ $project->webhook_format == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose the format that matches your webhook endpoint</p>
                        @error('webhook_format')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ $project->is_active ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">Project is active</label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 -mt-4 ml-6">Inactive projects will reject incoming logs</p>

                    <div class="pt-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Webhook Configuration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Webhook Security & Testing</h3>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Webhook Secret -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook Secret</label>
                            <button type="button" @click="showWebhookSecret = !showWebhookSecret" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                <span x-show="!showWebhookSecret">Show secret</span>
                                <span x-show="showWebhookSecret">Hide secret</span>
                            </button>
                        </div>
                        @if($project->webhook_secret)
                            <div class="relative">
                                <code x-show="showWebhookSecret" class="block bg-gray-100 dark:bg-gray-900 p-3 rounded-md text-sm text-gray-800 dark:text-gray-200 font-mono break-all">{{ $project->webhook_secret }}</code>
                                <code x-show="!showWebhookSecret" class="block bg-gray-100 dark:bg-gray-900 p-3 rounded-md text-sm text-gray-500 dark:text-gray-400">••••••••••••••••••••••••••••••••</code>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Use this secret to verify webhook authenticity. The signature is sent in the <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">X-Logger-Signature</code> header.
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No webhook secret configured. Generate one to enable signature verification.</p>
                        @endif
                        <div class="mt-3 flex space-x-3">
                            <button @click="showRegenerateWebhookSecretConfirm = true" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ $project->webhook_secret ? 'Regenerate Secret' : 'Generate Secret' }}
                            </button>
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <!-- Test Webhook -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Test Webhook</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Send a test message to verify your webhook configuration is working correctly.</p>
                        @if($project->webhook_url)
                            <form action="{{ route('projects.test-webhook', $project) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-indigo-300 dark:border-indigo-600 text-sm font-medium rounded-md text-indigo-700 dark:text-indigo-400 bg-white dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    Send Test Webhook
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Configure a webhook URL above to test.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Webhook Delivery History -->
            @if($webhookDeliveries->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Webhook Deliveries</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Response</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Attempts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($webhookDeliveries as $delivery)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($delivery->success)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                            ✓ Success
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                            ✗ Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ ucfirst($delivery->event_type) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($delivery->status_code)
                                        HTTP {{ $delivery->status_code }}
                                    @elseif($delivery->error_message)
                                        <span class="text-red-600 dark:text-red-400 truncate max-w-xs inline-block" title="{{ $delivery->error_message }}">{{ Str::limit($delivery->error_message, 40) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $delivery->attempt }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $delivery->created_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Danger Zone -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
                <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <h3 class="text-lg font-medium text-red-800 dark:text-red-400">Danger Zone</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Regenerate Key -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Regenerate Magic Key</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">This will invalidate the current key and require updating all clients</p>
                        </div>
                        <button @click="showRegenerateConfirm = true" type="button" class="inline-flex items-center px-3 py-2 border border-orange-300 dark:border-orange-600 text-sm font-medium rounded-md text-orange-700 dark:text-orange-400 bg-white dark:bg-gray-700 hover:bg-orange-50 dark:hover:bg-orange-900/20">
                            Regenerate Key
                        </button>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <!-- Truncate Logs -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Truncate All Logs</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Delete all log entries for this project. This cannot be undone.</p>
                        </div>
                        <button @click="showTruncateConfirm = true" type="button" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20">
                            Truncate Logs
                        </button>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <!-- Delete Project -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Delete Project</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Permanently delete this project and all its logs</p>
                        </div>
                        <button @click="showDeleteConfirm = true" type="button" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20">
                            Delete Project
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <!-- Project Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Info</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Project ID</dt>
                        <dd class="font-mono text-gray-900 dark:text-white break-all">{{ $project->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $project->created_at?->format('M d, Y H:i') ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Last Updated</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $project->updated_at?->format('M d, Y H:i') ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Integration Guide -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Integration</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Send logs to:</p>
                <code class="block bg-gray-100 dark:bg-gray-900 p-3 rounded-md text-sm text-gray-800 dark:text-gray-200 break-all">
                    POST {{ url('/api/ingest') }}
                </code>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-4 mb-2">With header:</p>
                <code class="block bg-gray-100 dark:bg-gray-900 p-3 rounded-md text-sm text-gray-800 dark:text-gray-200">
                    X-Project-Key: [your-key]
                </code>
            </div>

            <!-- Laravel Log Shipper -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ copiedComposer: false, copiedEnv: false }">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Laravel Log Shipper</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This application is designed to work with the <strong>Laravel Log Shipper</strong> package. Install it in your Laravel application to automatically ship logs here.
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Install via Composer:</p>
                <div class="relative group mb-4">
                    <code class="block bg-gray-100 dark:bg-gray-900 p-3 pr-10 rounded-md text-sm text-gray-800 dark:text-gray-200">composer require adminintelligence/laravel-log-shipper</code>
                    <button
                        @click="navigator.clipboard.writeText('composer require adminintelligence/laravel-log-shipper'); copiedComposer = true; setTimeout(() => copiedComposer = false, 2000)"
                        class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="{ 'text-green-600 dark:text-green-400': copiedComposer }"
                        title="Copy to clipboard"
                    >
                        <svg x-show="!copiedComposer" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg x-show="copiedComposer" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Add to your <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">.env</code>:</p>
                <div class="relative group mb-4">
                    <div class="bg-gray-100 dark:bg-gray-900 p-3 pr-10 rounded-md text-sm text-gray-800 dark:text-gray-200 font-mono overflow-x-auto">
                        <pre class="whitespace-pre">LOG_SHIPPER_ENABLED=true
LOG_SHIPPER_ENDPOINT={{ url('/api/ingest') }}
LOG_SHIPPER_KEY={{ $project->magic_key }}</pre>
                    </div>
                    <button
                        @click="navigator.clipboard.writeText(`LOG_SHIPPER_ENABLED=true\nLOG_SHIPPER_ENDPOINT={{ url('/api/ingest') }}\nLOG_SHIPPER_KEY={{ $project->magic_key }}`); copiedEnv = true; setTimeout(() => copiedEnv = false, 2000)"
                        class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="{ 'text-green-600 dark:text-green-400': copiedEnv }"
                        title="Copy to clipboard"
                    >
                        <svg x-show="!copiedEnv" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg x-show="copiedEnv" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>

                <a href="https://github.com/ADMIN-INTELLIGENCE-GmbH/laravel-log-shipper" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                    </svg>
                    View documentation on GitHub
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Regenerate Key Confirmation Modal -->
    <div x-show="showRegenerateConfirm" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showRegenerateConfirm" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRegenerateConfirm = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showRegenerateConfirm" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Regenerate Magic Key</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to regenerate the magic key? All clients using the current key will need to be updated.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('projects.regenerate-key', $project) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Regenerate
                        </button>
                    </form>
                    <button type="button" @click="showRegenerateConfirm = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Regenerate Webhook Secret Confirmation Modal -->
    <div x-show="showRegenerateWebhookSecretConfirm" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showRegenerateWebhookSecretConfirm" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRegenerateWebhookSecretConfirm = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showRegenerateWebhookSecretConfirm" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">{{ $project->webhook_secret ? 'Regenerate' : 'Generate' }} Webhook Secret</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($project->webhook_secret)
                                        This will generate a new secret. Any systems verifying webhook signatures will need to be updated with the new secret.
                                    @else
                                        This will generate a secret key that you can use to verify webhook authenticity on your receiving endpoint.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('projects.regenerate-webhook-secret', $project) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $project->webhook_secret ? 'Regenerate' : 'Generate' }}
                        </button>
                    </form>
                    <button type="button" @click="showRegenerateWebhookSecretConfirm = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Project Confirmation Modal -->
    <div x-show="showDeleteConfirm" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDeleteConfirm" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteConfirm = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showDeleteConfirm" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Delete Project</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this project? This action cannot be undone and will permanently delete all associated logs.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete Project
                        </button>
                    </form>
                    <button type="button" @click="showDeleteConfirm = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Truncate Logs Confirmation Modal -->
    <div x-show="showTruncateConfirm" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showTruncateConfirm" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTruncateConfirm = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showTruncateConfirm" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Truncate All Logs</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete all log entries for this project? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('projects.truncate-logs', $project) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Truncate All Logs
                        </button>
                    </form>
                    <button type="button" @click="showTruncateConfirm = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
