@extends('layouts.logger')

@section('title', $project->name . ' - Settings')

@section('content')
<div x-data="{ showRegenerateConfirm: false, showDeleteConfirm: false }">
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
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Receive notifications for errors and critical logs (Slack, Discord, Mattermost compatible)</p>
                        @error('webhook_url')
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
</div>
@endsection
