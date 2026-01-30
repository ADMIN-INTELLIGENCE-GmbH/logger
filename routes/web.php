<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FailingControllersController;
use App\Http\Controllers\GlobalDashboardController;
use App\Http\Controllers\LogExplorerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSettingsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Global Dashboard
    Route::get('/dashboard', [GlobalDashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])
        ->middleware('can:create,App\\Models\\Project')
        ->name('projects.store');
    Route::get('/projects/{project}/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:view,project')
        ->name('projects.dashboard');

    // Log Explorer
    Route::get('/projects/{project}/logs', [LogExplorerController::class, 'index'])
        ->middleware('can:view,project')
        ->name('projects.logs.index');
    Route::get('/projects/{project}/logs/{log}', [LogExplorerController::class, 'show'])
        ->middleware('can:view,project')
        ->name('projects.logs.show');
    Route::post('/projects/{project}/logs/{log}/analyze', [LogExplorerController::class, 'analyze'])
        ->middleware('can:view,project')
        ->name('projects.logs.analyze');
    Route::delete('/projects/{project}/logs/{log}', [LogExplorerController::class, 'destroy'])
        ->middleware('can:update,project')
        ->name('projects.logs.destroy');
    Route::post('/projects/{project}/logs/bulk-delete', [LogExplorerController::class, 'bulkDestroy'])
        ->middleware('can:update,project')
        ->name('projects.logs.bulk-destroy');

    // Failing Controllers
    Route::get('/projects/{project}/failing-controllers', [FailingControllersController::class, 'index'])
        ->middleware('can:view,project')
        ->name('projects.failing-controllers.index');

    // Project Settings
    Route::get('/projects/{project}/settings', [ProjectSettingsController::class, 'show'])
        ->middleware('can:update,project')
        ->name('projects.settings.show');
    Route::put('/projects/{project}/settings', [ProjectSettingsController::class, 'update'])
        ->middleware('can:update,project')
        ->name('projects.settings.update');
    Route::post('/projects/{project}/regenerate-key', [ProjectSettingsController::class, 'regenerateKey'])
        ->middleware('can:update,project')
        ->name('projects.regenerate-key');
    Route::post('/projects/{project}/regenerate-webhook-secret', [ProjectSettingsController::class, 'regenerateWebhookSecret'])
        ->middleware('can:update,project')
        ->name('projects.regenerate-webhook-secret');
    Route::post('/projects/{project}/test-webhook', [ProjectSettingsController::class, 'testWebhook'])
        ->middleware('can:update,project')
        ->name('projects.test-webhook');
    Route::post('/projects/{project}/truncate-logs', [ProjectSettingsController::class, 'truncateLogs'])
        ->middleware('can:update,project')
        ->name('projects.truncate-logs');
    Route::delete('/projects/{project}', [ProjectSettingsController::class, 'destroy'])
        ->middleware('can:update,project')
        ->name('projects.destroy');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/digest', [ProfileController::class, 'updateDigest'])->name('profile.digest.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard Preferences
    Route::post('/dashboard/preferences', [GlobalDashboardController::class, 'updatePreferences'])->name('dashboard.preferences.update');

    // User Administration (Admin only)
    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
