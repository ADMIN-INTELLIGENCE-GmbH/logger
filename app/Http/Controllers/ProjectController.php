<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * List all projects.
     */
    public function index(): View
    {
        $projects = Project::orderBy('name')->get();

        // Add 24h log count for each project
        $projects->each(function ($project) {
            $project->logs_count_24h = Log::where('project_id', $project->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();
        });

        return view('projects.index', compact('projects'));
    }

    /**
     * Create a new project.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'retention_days' => 'nullable|integer|in:7,14,30,90,-1',
            'webhook_url' => 'nullable|url|max:500',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'retention_days' => $validated['retention_days'] ?? 30,
            'webhook_url' => $validated['webhook_url'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('projects.dashboard', $project)
            ->with('success', 'Project created successfully. Your magic key: ' . $project->magic_key);
    }

    /**
     * Show a specific project.
     */
    public function show(Project $project): RedirectResponse
    {
        return redirect()->route('projects.dashboard', $project);
    }

    /**
     * Delete a project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
