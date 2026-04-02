@extends('layouts.logger')

@section('title', 'Edit External Check')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit External Check</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Update thresholds, tag selectors, and explicit project overrides.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="mb-6 space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <div>Endpoint: <span class="font-mono text-gray-900 dark:text-gray-100">{{ route('api.external-checks.issues', $externalCheck->slug) }}</span></div>
            <div>Token suffix: <span class="font-mono text-gray-900 dark:text-gray-100">{{ $externalCheck->token_last_eight }}</span></div>
        </div>

        @include('checks._form', [
            'externalCheck' => $externalCheck,
            'availableProjects' => $availableProjects,
            'formAction' => route('checks.update', $externalCheck),
            'formMethod' => 'PUT',
            'submitLabel' => 'Save Changes',
            'showCancel' => true,
        ])
    </div>
</div>
@endsection