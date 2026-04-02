@extends('layouts.logger')

@section('title', 'External Checks')

@section('content')
<div class="space-y-6" x-data="{
    copiedKey: null,
    async copyText(value, key) {
        try {
            await navigator.clipboard.writeText(value);
            this.copiedKey = key;
            setTimeout(() => {
                if (this.copiedKey === key) {
                    this.copiedKey = null;
                }
            }, 2000);
        } catch (error) {
            alert('Failed to copy to clipboard');
        }
    }
}">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">External Checks</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">Configure stable issue endpoints that automatically pick up matching projects.</p>
        </div>
    </div>

    @if(session('generated_token'))
        @php
            $generatedCurl = "curl -sS ".route('api.external-checks.issues', optional($checks->firstWhere('id', session('generated_check_id')))->slug ?? 'your-check-slug')." -H 'Authorization: Bearer ".session('generated_token')."'";
        @endphp
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-100">
            <h2 class="text-sm font-semibold">Copy this token now</h2>
            <p class="mt-1 text-sm">This token is only shown once for the selected external check.</p>
            <div class="mt-3 rounded-md bg-white/70 px-3 py-2 font-mono text-sm dark:bg-gray-950/40">{{ session('generated_token') }}</div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="copyText(@js($generatedCurl), 'generated-curl')"
                    class="inline-flex items-center px-3 py-2 border border-amber-300 dark:border-amber-700 text-sm font-medium rounded-md text-amber-900 dark:text-amber-100 bg-white/70 dark:bg-gray-950/40 hover:bg-white dark:hover:bg-gray-900">
                    <span x-show="copiedKey !== 'generated-curl'">Copy cURL</span>
                    <span x-show="copiedKey === 'generated-curl'">Copied cURL</span>
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-4">
            @forelse($checks as $check)
                @php
                    $storedToken = $check->plainTextToken();
                    $hasGeneratedToken = session('generated_check_id') === $check->id && session('generated_token');
                    $curlToken = $hasGeneratedToken ? session('generated_token') : $storedToken;
                    $curlCommand = "curl -sS ".route('api.external-checks.issues', $check->slug)." -H 'Authorization: Bearer ".$curlToken."'";
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $check->name }}</h2>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $check->enabled ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ $check->enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            @if($check->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $check->description }}</p>
                            @endif
                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <div>Endpoint: <span class="font-mono text-gray-900 dark:text-gray-100">{{ route('api.external-checks.issues', $check->slug) }}</span></div>
                                <div>Token suffix: <span class="font-mono text-gray-900 dark:text-gray-100">{{ $check->token_last_eight }}</span></div>
                                <div>Matches {{ $check->resolved_projects_count }} project{{ $check->resolved_projects_count === 1 ? '' : 's' }}</div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if($curlToken)
                                <button
                                    type="button"
                                    @click="copyText(@js($curlCommand), 'curl-{{ $check->id }}')"
                                    class="inline-flex items-center px-3 py-2 border border-emerald-300 dark:border-emerald-700 text-sm font-medium rounded-md text-emerald-700 dark:text-emerald-300 bg-white dark:bg-gray-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/30">
                                    <span x-show="copiedKey !== 'curl-{{ $check->id }}'">Copy cURL</span>
                                    <span x-show="copiedKey === 'curl-{{ $check->id }}'">Copied cURL</span>
                                </button>
                            @else
                                <span class="inline-flex items-center px-3 py-2 border border-amber-300 dark:border-amber-700 text-sm font-medium rounded-md text-amber-700 dark:text-amber-300 bg-white dark:bg-gray-700">
                                    Rotate token to enable cURL copy
                                </span>
                            @endif
                            <a href="{{ route('checks.edit', $check) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Edit</a>
                            <form action="{{ route('checks.rotate-token', $check) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-indigo-300 dark:border-indigo-700 text-sm font-medium rounded-md text-indigo-700 dark:text-indigo-300 bg-white dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">Rotate Token</button>
                            </form>
                            <form action="{{ route('checks.destroy', $check) }}" method="POST" onsubmit="return confirm('Delete this external check?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-700 text-sm font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/30">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Thresholds:</span>
                            level {{ $check->min_level }}, {{ $check->count_threshold }} hits in {{ $check->time_window_minutes }} min
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Tags:</span>
                            {{ collect($check->selector_tags ?? [])->join(', ') ?: 'All visible projects' }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Project Grouping:</span>
                            {{ $check->group_across_projects ? 'Across projects' : 'Per project' }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Memory:</span>
                            {{ $check->memory_percent_gte !== null ? $check->memory_percent_gte.'%' : 'Off' }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Disk:</span>
                            {{ $check->disk_percent_gte !== null ? $check->disk_percent_gte.'%' : 'Off' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-600 dark:text-gray-400">
                    No external checks yet.
                </div>
            @endforelse
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 h-fit">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Create External Check</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use tags to auto-include future projects without changing the external caller.</p>
            <div class="mt-6">
                @include('checks._form', [
                    'externalCheck' => null,
                    'availableProjects' => $availableProjects,
                    'formAction' => route('checks.store'),
                    'formMethod' => 'POST',
                    'submitLabel' => 'Create Check',
                    'showCancel' => false,
                ])
            </div>
        </div>
    </div>
</div>
@endsection