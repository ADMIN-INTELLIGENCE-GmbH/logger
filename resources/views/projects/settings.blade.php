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

                    <!-- Allowed Domains -->
                    <div>
                        <label for="allowed_domains" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Allowed Domains (CORS & Origin)</label>
                        <div class="mt-1">
                            <textarea name="allowed_domains" id="allowed_domains" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2" placeholder="example.com&#10;*.example.org">{{ $project->allowed_domains ? implode("\n", $project->allowed_domains) : '' }}</textarea>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Enter one domain per line. Use <code>*.domain.com</code> for wildcards. Leave empty to allow all domains (not recommended for public sites).
                        </p>
                        @error('allowed_domains')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tags -->
                    <div x-data="tagManager({{ json_encode($project->tags->pluck('name')) }})">
                        <label for="tag_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                        
                        <!-- Tag Input with Autocomplete -->
                        <div class="relative mt-2">
                            <input 
                                type="text" 
                                id="tag_input"
                                x-model="tagInput"
                                @input="fetchSuggestions"
                                @keydown.enter.prevent="selectHighlighted"
                                @keydown.down.prevent="navigateSuggestions('down')"
                                @keydown.up.prevent="navigateSuggestions('up')"
                                @keydown.escape="showSuggestions = false"
                                @focus="showSuggestions = true"
                                autocomplete="off"
                                placeholder="Type to add or search tags..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                            
                            <!-- Suggestions Dropdown -->
                            <div x-show="showSuggestions && (suggestions.length > 0 || tagInput.length > 0)" 
                                 x-cloak
                                 @click.away="showSuggestions = false"
                                 class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                <template x-if="suggestions.length > 0">
                                    <div>
                                        <template x-for="(suggestion, index) in suggestions" :key="suggestion">
                                            <div 
                                                @click="selectSuggestion(suggestion)"
                                                :class="{
                                                    'bg-indigo-100 dark:bg-indigo-900/40': index === highlightedIndex,
                                                    'hover:bg-indigo-50 dark:hover:bg-indigo-900/20': index !== highlightedIndex
                                                }"
                                                class="cursor-pointer select-none relative py-2 pl-3 pr-9">
                                                <span class="block truncate text-gray-900 dark:text-white" x-text="suggestion"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="suggestions.length === 0 && tagInput.length > 0">
                                    <div 
                                        @click="addTag"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-green-50 dark:hover:bg-green-900/20">
                                        <span class="block text-gray-600 dark:text-gray-300">
                                            Create new tag: <span class="font-medium text-green-600 dark:text-green-400" x-text="tagInput"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Selected Tags -->
                        <div x-show="selectedTags.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <template x-for="(tag, index) in selectedTags" :key="index">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                    <span x-text="tag"></span>
                                    <button type="button" @click="removeTag(index)" class="ml-1.5 inline-flex items-center justify-center w-4 h-4 text-indigo-600 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-100">
                                        <i class="mdi mdi-close text-sm"></i>
                                    </button>
                                </span>
                            </template>
                        </div>

                        <!-- Hidden input to submit tags -->
                        <input type="hidden" name="tags" :value="JSON.stringify(selectedTags)">
                        
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Add tags like "production", "staging", "local", or "high-priority". Press Enter to add.
                        </p>
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
                            @foreach(\App\Models\Log::LEVELS as $level)
                                <option value="{{ $level }}" {{ $project->webhook_threshold == $level ? 'selected' : '' }}>
                                    {{ ucfirst($level) }}{{ $level === 'debug' ? ' (all logs)' : ' and above' }}
                                </option>
                            @endforeach
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
                                    <i class="mdi mdi-send mr-2"></i>
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
            <!-- .env Configurator -->
            <div x-data="envConfigurator({ endpoint: '{{ url('/api/ingest') }}', statsEndpoint: '{{ url('/api/stats') }}', key: '{{ $project->magic_key }}' })" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">.env Configurator</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Generate configuration for your .env file.</p>
                </div>
                <div class="p-6">
                    <!-- Step 1: Select Features -->
                    <div x-show="step === 1" class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Select Features</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <template x-for="feature in features" :key="feature.id">
                                <div class="relative flex items-start p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" 
                                     :class="feature.selected ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-300 dark:border-gray-600'"
                                     @click="feature.selected = !feature.selected">
                                    <div class="min-w-0 flex-1 text-sm">
                                        <label :for="'feature-' + feature.id" class="font-medium text-gray-700 dark:text-gray-300 select-none cursor-pointer" x-text="feature.name"></label>
                                    </div>
                                    <div class="ml-3 flex items-center h-5">
                                        <input :id="'feature-' + feature.id" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" x-model="feature.selected">
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="pt-4 flex justify-end">
                            <button @click="nextStep()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Next: Configure
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Configure Options -->
                    <div x-show="step === 2" class="space-y-6">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Configure Options</h4>
                        <template x-for="feature in features.filter(f => f.selected)" :key="feature.id">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0 last:pb-0">
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white mb-4" x-text="feature.name"></h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <template x-for="field in fields[feature.id]" :key="field.key">
                                        <div class="col-span-1">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase" x-text="field.label"></label>
                                            
                                            <template x-if="field.type === 'select'">
                                                <select x-model="configs[feature.id][field.key]" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                                                    <template x-for="option in field.options" :key="option">
                                                        <option :value="option" x-text="option"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            
                                            <template x-if="field.type !== 'select'">
                                                <input :type="field.type" x-model="configs[feature.id][field.key]" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                                            </template>

                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-text="field.description"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <div class="pt-4 flex justify-between">
                            <button @click="prevStep()" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                                Back
                            </button>
                            <button @click="nextStep()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Generate .env
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Result -->
                    <div x-show="step === 3" class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white">Generated Configuration</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">You can copy this and paste it directly into your .env file.</p>
                        <div class="relative group" x-data="{ copied: false }">
                            <div class="relative bg-gray-900 dark:bg-gray-950 rounded-md border border-gray-300 dark:border-gray-600 overflow-auto max-h-[500px]">
                                <pre class="text-gray-300 font-mono text-sm p-4 whitespace-pre-wrap break-words" x-text="generatedEnv"></pre>
                            </div>
                            <button
                                @click="navigator.clipboard.writeText(generatedEnv); copied = true; setTimeout(() => copied = false, 2000)"
                                class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                :class="{ 'text-green-600 dark:text-green-400': copied }"
                                title="Copy to clipboard"
                            >
                                <i x-show="!copied" class="mdi mdi-content-copy"></i>
                                <i x-show="copied" x-cloak class="mdi mdi-check"></i>
                            </button>
                        </div>
                        <div class="pt-4 flex justify-start">
                            <button @click="step = 1" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                                Start Over
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function envConfigurator(defaults = {}) {
                    return {
                        step: 1,
                        features: [
                            { id: 'log_shipper', name: 'Core Configuration', selected: true },
                            { id: 'queue', name: 'Queue Settings', selected: false },
                            { id: 'batch', name: 'Batch Shipping', selected: false },
                            { id: 'status', name: 'Status Monitoring', selected: false },
                        ],
                        fields: {
                            log_shipper: [
                                { key: 'enabled', label: 'Enabled', type: 'select', options: ['true', 'false'], description: 'Enable or disable the log shipper.' },
                                { key: 'endpoint', label: 'Endpoint', type: 'text', description: 'The URL where logs will be sent.' },
                                { key: 'key', label: 'API Key', type: 'text', description: 'The magic key that identifies this project.' },
                                { key: 'fallback', label: 'Fallback Channel', type: 'text', description: 'Local channel to write to if shipping fails (e.g., "daily", "single"). Set to "null" to disable.' }
                            ],
                            queue: [
                                { key: 'connection', label: 'Queue Connection', type: 'text', description: 'The queue connection to use (e.g., "redis", "database", "sync").' },
                                { key: 'name', label: 'Queue Name', type: 'text', description: 'The specific queue name to dispatch jobs to.' }
                            ],
                            batch: [
                                { key: 'enabled', label: 'Batch Enabled', type: 'select', options: ['true', 'false'], description: 'Buffer logs and ship them in batches.' },
                                { key: 'driver', label: 'Batch Driver', type: 'select', options: ['redis', 'cache'], description: 'Storage driver for buffering logs.' },
                                { key: 'size', label: 'Batch Size', type: 'number', description: 'Number of logs to ship at once.' },
                                { key: 'interval', label: 'Batch Interval', type: 'number', description: 'Minutes between batch runs.' }
                            ],
                            status: [
                                { key: 'enabled', label: 'Status Enabled', type: 'select', options: ['true', 'false'], description: 'Enable automatic status pushing.' },
                                { key: 'endpoint', label: 'Status Endpoint', type: 'text', description: 'The endpoint to send status reports to.' },
                                { key: 'interval', label: 'Status Interval', type: 'number', description: 'Minutes between status reports.' }
                            ]
                        },
                        configs: {
                            log_shipper: {
                                enabled: 'true',
                                endpoint: defaults.endpoint || '',
                                key: defaults.key || '',
                                fallback: 'null'
                            },
                            queue: {
                                connection: 'default',
                                name: 'default'
                            },
                            batch: {
                                enabled: 'false',
                                driver: 'redis',
                                size: '100',
                                interval: '1'
                            },
                            status: {
                                enabled: 'false',
                                endpoint: defaults.statsEndpoint || '',
                                interval: '5'
                            }
                        },
                        generatedEnv: '',
                        
                        nextStep() {
                            if (this.step === 1) {
                                if (this.features.filter(f => f.selected).length === 0) {
                                    alert('Please select at least one feature.');
                                    return;
                                }
                                this.step = 2;
                            } else if (this.step === 2) {
                                this.generateEnv();
                                this.step = 3;
                            }
                        },
                        
                        prevStep() {
                            this.step--;
                        },
                        
                        generateEnv() {
                            let output = '';
                            this.features.filter(f => f.selected).forEach(feature => {
                                output += `### ${feature.name}\n`;
                                const config = this.configs[feature.id];
                                for (const [key, value] of Object.entries(config)) {
                                    const envKey = this.getEnvKey(feature.id, key);
                                    output += `${envKey}=${value}\n`;
                                }
                                output += '\n';
                            });
                            this.generatedEnv = output;
                        },
                        
                        getEnvKey(featureId, key) {
                            const map = {
                                log_shipper: {
                                    enabled: 'LOG_SHIPPER_ENABLED',
                                    endpoint: 'LOG_SHIPPER_ENDPOINT',
                                    key: 'LOG_SHIPPER_KEY',
                                    fallback: 'LOG_SHIPPER_FALLBACK'
                                },
                                queue: {
                                    connection: 'LOG_SHIPPER_QUEUE',
                                    name: 'LOG_SHIPPER_QUEUE_NAME'
                                },
                                batch: {
                                    enabled: 'LOG_SHIPPER_BATCH_ENABLED',
                                    driver: 'LOG_SHIPPER_BATCH_DRIVER',
                                    size: 'LOG_SHIPPER_BATCH_SIZE',
                                    interval: 'LOG_SHIPPER_BATCH_INTERVAL'
                                },
                                status: {
                                    enabled: 'LOG_SHIPPER_STATUS_ENABLED',
                                    endpoint: 'LOG_SHIPPER_STATUS_ENDPOINT',
                                    interval: 'LOG_SHIPPER_STATUS_INTERVAL'
                                }
                            };
                            return map[featureId][key] || key.toUpperCase();
                        }
                    }
                }
            </script>

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

            <!-- API Key -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ showKey: false, copiedKey: false }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">API Key</h3>
                    <button type="button" @click="showKey = !showKey" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                        <span x-show="!showKey">Show key</span>
                        <span x-show="showKey">Hide key</span>
                    </button>
                </div>
                <div class="relative group">
                    <div class="relative bg-gray-100 dark:bg-gray-900 rounded-md overflow-auto max-h-24">
                        <code x-show="showKey" class="block p-3 pr-10 text-sm text-gray-800 dark:text-gray-200 font-mono break-all">{{ $project->magic_key }}</code>
                        <code x-show="!showKey" class="block p-3 pr-10 text-sm text-gray-500 dark:text-gray-400 break-all">••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••</code>
                    </div>
                    <button
                        @click="navigator.clipboard.writeText('{{ $project->magic_key }}'); copiedKey = true; setTimeout(() => copiedKey = false, 2000)"
                        class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="{ 'text-green-600 dark:text-green-400': copiedKey }"
                        title="Copy to clipboard"
                    >
                        <i x-show="!copiedKey" class="mdi mdi-content-copy"></i>
                        <i x-show="copiedKey" x-cloak class="mdi mdi-check"></i>
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Use this key in the <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">X-Project-Key</code> header when sending logs.
                </p>
                <div class="mt-3">
                    <button @click="showRegenerateConfirm = true" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <i class="mdi mdi-refresh mr-2"></i>
                        Regenerate Key
                    </button>
                </div>
            </div>

            <!-- Integration Guide -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ copiedEndpoint: false, copiedHeader: false }">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Integration</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Send logs to:</p>
                <div class="relative group mb-6">
                    <code class="block bg-gray-100 dark:bg-gray-900 p-3 pr-10 rounded-md text-sm text-gray-800 dark:text-gray-200 break-all">POST {{ url('/api/ingest') }}</code>
                    <button
                        @click="navigator.clipboard.writeText('{{ url('/api/ingest') }}'); copiedEndpoint = true; setTimeout(() => copiedEndpoint = false, 2000)"
                        class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="{ 'text-green-600 dark:text-green-400': copiedEndpoint }"
                        title="Copy to clipboard"
                    >
                        <i x-show="!copiedEndpoint" class="mdi mdi-content-copy"></i>
                        <i x-show="copiedEndpoint" x-cloak class="mdi mdi-check"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">With header:</p>
                <div class="relative group">
                    <div class="relative bg-gray-100 dark:bg-gray-900 rounded-md overflow-auto max-h-24">
                        <code class="block p-3 pr-10 text-sm text-gray-800 dark:text-gray-200 break-all">X-Project-Key: {{ $project->magic_key }}</code>
                    </div>
                    <button
                        @click="navigator.clipboard.writeText('X-Project-Key: {{ $project->magic_key }}'); copiedHeader = true; setTimeout(() => copiedHeader = false, 2000)"
                        class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="{ 'text-green-600 dark:text-green-400': copiedHeader }"
                        title="Copy to clipboard"
                    >
                        <i x-show="!copiedHeader" class="mdi mdi-content-copy"></i>
                        <i x-show="copiedHeader" x-cloak class="mdi mdi-check"></i>
                    </button>
                </div>
            </div>

            <!-- Laravel Log Shipper -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="{ copiedComposer: false, copiedEnv: false }">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Laravel Log Shipper</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This application is designed to work with the <strong>Laravel Log Shipper</strong> package. Install it in your Laravel application to automatically ship logs here.
                </p>

                <div class="bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="mdi mdi-information-outline text-xl text-indigo-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                Need a custom configuration? Use the <a href="#" @click.prevent="document.querySelector('[x-data^=\'envConfigurator\']').scrollIntoView({behavior: 'smooth'})" class="font-medium underline hover:text-indigo-600 dark:hover:text-indigo-200">.env Configurator</a> on the left to generate a complete configuration for your project.
                            </p>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Install via Composer:</p>
                <div class="relative group mb-4">
                    <code class="block bg-gray-100 dark:bg-gray-900 p-3 pr-10 rounded-md text-sm text-gray-800 dark:text-gray-200">composer require adminintelligence/laravel-log-shipper</code>
                    <button
                        @click="navigator.clipboard.writeText('composer require adminintelligence/laravel-log-shipper'); copiedComposer = true; setTimeout(() => copiedComposer = false, 2000)"
                        class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="{ 'text-green-600 dark:text-green-400': copiedComposer }"
                        title="Copy to clipboard"
                    >
                        <i x-show="!copiedComposer" class="mdi mdi-content-copy"></i>
                        <i x-show="copiedComposer" x-cloak class="mdi mdi-check"></i>
                    </button>
                </div>

                <a href="https://github.com/ADMIN-INTELLIGENCE-GmbH/laravel-log-shipper" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    <i class="mdi mdi-github text-base mr-1.5"></i>
                    View documentation on GitHub
                    <i class="mdi mdi-open-in-new text-xs ml-1"></i>
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
                            <i class="mdi mdi-alert text-2xl text-orange-600 dark:text-orange-400"></i>
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
                            <i class="mdi mdi-key text-2xl text-indigo-600 dark:text-indigo-400"></i>
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
                            <i class="mdi mdi-delete text-2xl text-red-600 dark:text-red-400"></i>
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
                            <i class="mdi mdi-delete-sweep text-2xl text-red-600 dark:text-red-400"></i>
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

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tagManager', (initialTags = []) => ({
        selectedTags: initialTags,
        tagInput: '',
        suggestions: [],
        showSuggestions: false,
        debounceTimer: null,
        highlightedIndex: -1,

        async fetchSuggestions() {
            clearTimeout(this.debounceTimer);
            
            if (this.tagInput.length === 0) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }

            this.debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`/api/tags/search?query=${encodeURIComponent(this.tagInput)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    // Filter out already selected tags
                    this.suggestions = data.tags.filter(tag => 
                        !this.selectedTags.includes(tag) && 
                        tag.toLowerCase().includes(this.tagInput.toLowerCase())
                    );
                    this.highlightedIndex = -1;
                    this.showSuggestions = true;
                } catch (error) {
                    console.error('Error fetching tag suggestions:', error);
                    this.suggestions = [];
                }
            }, 300);
        },

        addTag() {
            const tag = this.tagInput.trim();
            if (tag && !this.selectedTags.includes(tag)) {
                this.selectedTags.push(tag);
                this.tagInput = '';
                this.suggestions = [];
                this.showSuggestions = false;
                this.highlightedIndex = -1;
            }
        },

        navigateSuggestions(direction) {
            if (this.suggestions.length === 0) return;
            
            if (direction === 'down') {
                this.highlightedIndex = (this.highlightedIndex + 1) % this.suggestions.length;
            } else if (direction === 'up') {
                this.highlightedIndex = this.highlightedIndex <= 0 
                    ? this.suggestions.length - 1 
                    : this.highlightedIndex - 1;
            }
        },

        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.highlightedIndex < this.suggestions.length) {
                this.selectSuggestion(this.suggestions[this.highlightedIndex]);
            } else {
                this.addTag();
            }
        },

        selectSuggestion(tag) {
            if (!this.selectedTags.includes(tag)) {
                this.selectedTags.push(tag);
                this.tagInput = '';
                this.suggestions = [];
                this.showSuggestions = false;
                this.highlightedIndex = -1;
            }
        },

        removeTag(index) {
            this.selectedTags.splice(index, 1);
        }
    }));
});
</script>

@endsection
