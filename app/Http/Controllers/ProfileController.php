<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $project = null; // Ensure project-specific nav is hidden

        return view('profile.edit', [
            'user' => $request->user(),
            'project' => $project,
            'tokens' => $request->user()->tokens()->latest()->get(),
        ]);
    }

    /**
     * Create an API token for the MCP endpoint. The plain text is shown once.
     */
    public function storeToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token_name' => 'required|string|max:100',
            'token_days' => 'required|integer|in:30,90,365',
        ]);

        $token = $request->user()->createToken(
            $validated['token_name'],
            ['mcp'],
            now()->addDays((int) $validated['token_days']),
        );

        return Redirect::route('profile.edit')
            ->with('status', 'token-created')
            ->with('new_token', $token->plainTextToken);
    }

    /**
     * Revoke one of the user's API tokens.
     */
    public function destroyToken(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return Redirect::route('profile.edit')->with('status', 'token-revoked');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's daily digest settings.
     */
    public function updateDigest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'daily_digest_at' => ['required_if:daily_digest_enabled,true', 'date_format:H:i'],
            'daily_digest_settings' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $user->daily_digest_enabled = $request->boolean('daily_digest_enabled');

        if ($user->daily_digest_enabled) {
            $user->daily_digest_at = $request->input('daily_digest_at');
        }

        $user->daily_digest_settings = $request->input('daily_digest_settings', []);

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'digest-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
