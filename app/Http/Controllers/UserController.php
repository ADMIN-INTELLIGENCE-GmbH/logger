<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $users = User::orderBy('id')->get();
        $project = null; // Ensure project-specific nav is hidden

        return view('users.index', compact('users', 'project'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $project = null; // Ensure project-specific nav is hidden
        $projects = Project::orderBy('name')->get();

        return view('users.create', compact('project', 'projects'));
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => $request->validated('role'),
            'email_verified_at' => now(),
        ]);

        $this->syncProjectPermissions($user, $request->input('project_permissions', []));

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View
    {
        $project = null; // Ensure project-specific nav is hidden
        $projects = Project::orderBy('name')->get();
        $userProjectPermissions = $user->projects()
            ->pluck('project_user.permission', 'projects.id')
            ->toArray();

        return view('users.edit', compact('user', 'project', 'projects', 'userProjectPermissions'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);
        $this->syncProjectPermissions($user, $request->input('project_permissions', []));

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Sync project permissions for the given user.
     *
     * @param  array<string, string|null>  $permissions
     */
    protected function syncProjectPermissions(User $user, array $permissions): void
    {
        $validProjectIds = Project::whereIn('id', array_keys($permissions))
            ->pluck('id')
            ->all();

        $syncData = collect($permissions)
            ->filter(fn ($permission) => in_array($permission, Project::PERMISSIONS, true))
            ->only($validProjectIds)
            ->mapWithKeys(function ($permission, $projectId) {
                return [$projectId => ['permission' => $permission]];
            });

        $user->projects()->sync($syncData->all());
    }
}
