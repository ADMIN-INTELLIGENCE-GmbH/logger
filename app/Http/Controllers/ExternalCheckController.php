<?php

namespace App\Http\Controllers;

use App\Models\ExternalCheck;
use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExternalCheckController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $availableProjects = Project::query()
            ->visibleTo($user)
            ->with('tags')
            ->orderBy('name')
            ->get();

        $checks = ExternalCheck::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get()
            ->map(function (ExternalCheck $externalCheck) {
                $externalCheck->resolved_projects_count = $externalCheck->resolveProjects()->count();

                return $externalCheck;
            });

        $project = null;

        return view('checks.index', compact('checks', 'availableProjects', 'project'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCheck($request);
        $check = new ExternalCheck($validated);
        $check->user()->associate($request->user());
        $plainTextToken = str()->random(40);
        $check->fillTokenFromPlainText($plainTextToken);
        $check->save();

        return redirect()->route('checks.index')
            ->with('success', 'External check created successfully.')
            ->with('generated_token', $plainTextToken)
            ->with('generated_check_id', $check->id);
    }

    public function edit(Request $request, ExternalCheck $externalCheck): View
    {
        $this->authorize('view', $externalCheck);

        $availableProjects = Project::query()
            ->visibleTo($request->user())
            ->with('tags')
            ->orderBy('name')
            ->get();

        $project = null;

        return view('checks.edit', compact('externalCheck', 'availableProjects', 'project'));
    }

    public function update(Request $request, ExternalCheck $externalCheck): RedirectResponse
    {
        $this->authorize('update', $externalCheck);

        $externalCheck->update($this->validateCheck($request));

        return redirect()->route('checks.index')
            ->with('success', 'External check updated successfully.');
    }

    public function rotateToken(Request $request, ExternalCheck $externalCheck): RedirectResponse
    {
        $this->authorize('update', $externalCheck);

        $plainTextToken = $externalCheck->rotateToken();

        return redirect()->route('checks.index')
            ->with('success', 'External check token rotated successfully.')
            ->with('generated_token', $plainTextToken)
            ->with('generated_check_id', $externalCheck->id);
    }

    public function destroy(ExternalCheck $externalCheck): RedirectResponse
    {
        $this->authorize('delete', $externalCheck);

        $externalCheck->delete();

        return redirect()->route('checks.index')
            ->with('success', 'External check deleted successfully.');
    }

    protected function validateCheck(Request $request): array
    {
        $visibleProjectIds = Project::query()
            ->visibleTo($request->user())
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'enabled' => 'sometimes|boolean',
            'min_level' => ['required', Rule::in(Log::LEVELS)],
            'time_window_minutes' => 'required|integer|min:1|max:10080',
            'count_threshold' => 'required|integer|min:1|max:100000',
            'group_by' => 'required|array|min:1',
            'group_by.*' => ['required', Rule::in(ExternalCheck::GROUPABLE_FIELDS)],
            'group_across_projects' => 'sometimes|boolean',
            'selector_tags' => 'nullable|string',
            'included_project_ids' => 'nullable|array',
            'included_project_ids.*' => ['required', Rule::in($visibleProjectIds)],
            'excluded_project_ids' => 'nullable|array',
            'excluded_project_ids.*' => ['required', Rule::in($visibleProjectIds)],
            'memory_percent_gte' => 'nullable|numeric|min:0|max:100',
            'disk_percent_gte' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['selector_tags'] = ExternalCheck::normalizeTagNames(
            preg_split('/[\r\n,]+/', $validated['selector_tags'] ?? '') ?: []
        );
        $validated['included_project_ids'] = array_values($validated['included_project_ids'] ?? []);
        $validated['excluded_project_ids'] = array_values($validated['excluded_project_ids'] ?? []);
        $validated['enabled'] = (bool) ($validated['enabled'] ?? false);
        $validated['group_across_projects'] = (bool) ($validated['group_across_projects'] ?? false);

        return $validated;
    }
}
