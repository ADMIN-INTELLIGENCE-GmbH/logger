@php
    $selectedGroupBy = old('group_by', $externalCheck->group_by ?? \App\Models\ExternalCheck::DEFAULT_GROUP_BY);
    $includedProjectIds = old('included_project_ids', $externalCheck->included_project_ids ?? []);
    $excludedProjectIds = old('excluded_project_ids', $externalCheck->excluded_project_ids ?? []);
    $selectorTags = old('selector_tags', isset($externalCheck) ? implode(', ', $externalCheck->selector_tags ?? []) : '');
@endphp

<form action="{{ $formAction }}" method="POST" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $externalCheck->name ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">{{ old('description', $externalCheck->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center">
        <input type="hidden" name="enabled" value="0">
        <input type="checkbox" name="enabled" id="enabled" value="1" {{ old('enabled', $externalCheck->enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded">
        <label for="enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">Enable this external check</label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="min_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Log Level</label>
            <select name="min_level" id="min_level" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                @foreach(\App\Models\Log::LEVELS as $level)
                    <option value="{{ $level }}" {{ old('min_level', $externalCheck->min_level ?? 'error') === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                @endforeach
            </select>
            @error('min_level')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="time_window_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time Window (minutes)</label>
            <input type="number" min="1" max="10080" name="time_window_minutes" id="time_window_minutes" value="{{ old('time_window_minutes', $externalCheck->time_window_minutes ?? 60) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            @error('time_window_minutes')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="count_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Count Threshold</label>
            <input type="number" min="1" max="100000" name="count_threshold" id="count_threshold" value="{{ old('count_threshold', $externalCheck->count_threshold ?? 5) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            @error('count_threshold')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Group Repeated Errors By</label>
        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach(\App\Models\ExternalCheck::GROUPABLE_FIELDS as $field)
                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="group_by[]" value="{{ $field }}" {{ in_array($field, $selectedGroupBy, true) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">{{ str_replace('_', ' ', ucfirst($field)) }}</span>
                </label>
            @endforeach
        </div>
        @error('group_by')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('group_by.*')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-start">
        <input type="hidden" name="group_across_projects" value="0">
        <input type="checkbox" name="group_across_projects" id="group_across_projects" value="1" {{ old('group_across_projects', $externalCheck->group_across_projects ?? false) ? 'checked' : '' }} class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded">
        <div class="ml-2">
            <label for="group_across_projects" class="block text-sm text-gray-900 dark:text-white">Group identical issues across projects</label>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">When disabled, the same error in two projects will produce two issues. When enabled, matching errors can collapse into one issue with multiple affected projects.</p>
        </div>
    </div>

    <div>
        <label for="selector_tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auto-Include Tags</label>
        <input type="text" name="selector_tags" id="selector_tags" value="{{ $selectorTags }}" placeholder="production, customer-a, critical" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Projects with any of these tags will be included automatically, including future projects.</p>
        @error('selector_tags')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="included_project_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Explicitly Include Projects</label>
            <select name="included_project_ids[]" id="included_project_ids" multiple size="8" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                @foreach($availableProjects as $projectOption)
                    <option value="{{ $projectOption->id }}" {{ in_array($projectOption->id, $includedProjectIds, true) ? 'selected' : '' }}>
                        {{ $projectOption->name }}{{ $projectOption->tags->isNotEmpty() ? ' ['.$projectOption->tags->pluck('name')->join(', ').']' : '' }}
                    </option>
                @endforeach
            </select>
            @error('included_project_ids')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('included_project_ids.*')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="excluded_project_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Explicitly Exclude Projects</label>
            <select name="excluded_project_ids[]" id="excluded_project_ids" multiple size="8" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
                @foreach($availableProjects as $projectOption)
                    <option value="{{ $projectOption->id }}" {{ in_array($projectOption->id, $excludedProjectIds, true) ? 'selected' : '' }}>
                        {{ $projectOption->name }}{{ $projectOption->tags->isNotEmpty() ? ' ['.$projectOption->tags->pluck('name')->join(', ').']' : '' }}
                    </option>
                @endforeach
            </select>
            @error('excluded_project_ids')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('excluded_project_ids.*')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="memory_percent_gte" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Memory Threshold (%)</label>
            <input type="number" min="0" max="100" step="0.1" name="memory_percent_gte" id="memory_percent_gte" value="{{ old('memory_percent_gte', $externalCheck->memory_percent_gte ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            @error('memory_percent_gte')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="disk_percent_gte" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Disk Threshold (%)</label>
            <input type="number" min="0" max="100" step="0.1" name="disk_percent_gte" id="disk_percent_gte" value="{{ old('disk_percent_gte', $externalCheck->disk_percent_gte ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2">
            @error('disk_percent_gte')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="pt-2 flex items-center gap-3">
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ $submitLabel }}
        </button>
        @if(!empty($showCancel) && $showCancel)
            <a href="{{ route('checks.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                Cancel
            </a>
        @endif
    </div>
</form>