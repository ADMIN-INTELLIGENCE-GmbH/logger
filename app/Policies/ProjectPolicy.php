<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($project->relationLoaded('users')) {
            return (bool) $project->users->firstWhere('id', $user->id);
        }

        return $user->projects()
            ->where('projects.id', $project->id)
            ->exists();
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($project->relationLoaded('users')) {
            $membership = $project->users->firstWhere('id', $user->id);

            return $membership && $membership->pivot->permission === Project::PERMISSION_EDIT;
        }

        return $user->projects()
            ->where('projects.id', $project->id)
            ->wherePivot('permission', Project::PERMISSION_EDIT)
            ->exists();
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
