<?php

namespace App\Http\Controllers;

use App\Listeners\WebhookDispatcher;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectSettingsController extends Controller
{
    /**
     * Get project settings.
     */
    public function show(Project $project): View
    {
        $project->load('tags');
        
        $webhookDeliveries = $project->webhookDeliveries()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('projects.settings', compact('project', 'webhookDeliveries'));
    }

    /**
     * Update project settings.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'retention_days' => [
                'sometimes',
                'required',
                'integer',
                Rule::in([7, 14, 30, 90, -1]),
            ],
            'webhook_url' => 'sometimes|nullable|url|max:500',
            'webhook_enabled' => 'sometimes|boolean',
            'webhook_threshold' => [
                'sometimes',
                'required',
                Rule::in(\App\Models\Log::LEVELS),
            ],
            'webhook_format' => [
                'sometimes',
                'required',
                Rule::in(array_keys(Project::WEBHOOK_FORMATS)),
            ],
            'is_active' => 'sometimes|boolean',
            'tags' => 'sometimes|nullable|json',
        ]);

        $project->update($validated);

        // Handle tags
        if ($request->has('tags')) {
            // Get the currently attached tags before syncing
            $previousTagIds = $project->tags()->select('tags.id')->pluck('tags.id')->toArray();
            
            $tagNames = json_decode($request->input('tags'), true) ?? [];
            $tagIds = [];
            
            foreach ($tagNames as $tagName) {
                $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
            
            $project->tags()->sync($tagIds);
            
            // Clean up orphaned tags (tags that are no longer used by any project)
            $removedTagIds = array_diff($previousTagIds, $tagIds);
            if (! empty($removedTagIds)) {
                Tag::whereIn('id', $removedTagIds)
                    ->get()
                    ->each(function ($tag) {
                        if (! $tag->isInUse()) {
                            $tag->delete();
                        }
                    });
            }
        }

        return redirect()->route('projects.settings.show', $project)
            ->with('success', 'Project settings updated successfully.');
    }

    /**
     * Regenerate the magic key.
     */
    public function regenerateKey(Project $project): RedirectResponse
    {
        $project->regenerateMagicKey();

        return redirect()->route('projects.settings.show', $project)
            ->with('success', 'Magic key regenerated successfully. New key: '.$project->magic_key);
    }

    /**
     * Regenerate the webhook secret.
     */
    public function regenerateWebhookSecret(Project $project): RedirectResponse
    {
        $project->regenerateWebhookSecret();

        return redirect()->route('projects.settings.show', $project)
            ->with('success', 'Webhook secret regenerated successfully.');
    }

    /**
     * Send a test webhook.
     */
    public function testWebhook(Project $project): RedirectResponse
    {
        if (! $project->hasWebhookUrl()) {
            return redirect()->route('projects.settings.show', $project)
                ->with('error', 'No webhook URL configured.');
        }

        $delivery = WebhookDispatcher::sendTestWebhook($project);

        if ($delivery->success) {
            return redirect()->route('projects.settings.show', $project)
                ->with('success', 'Test webhook sent successfully! Status: '.$delivery->status_code);
        }

        $errorMessage = $delivery->error_message ?? "HTTP {$delivery->status_code}";

        return redirect()->route('projects.settings.show', $project)
            ->with('error', 'Webhook test failed: '.$errorMessage);
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

    /**
     * Truncate all logs for a project.
     */
    public function truncateLogs(Project $project): RedirectResponse
    {
        $count = $project->logs()->count();
        $project->logs()->delete();

        return redirect()->route('projects.settings.show', $project)
            ->with('success', "Deleted {$count} log entries.");
    }
}
