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
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/dashboard', [DashboardController::class, 'index'])->name('projects.dashboard');

    // Log Explorer
    Route::get('/projects/{project}/logs', [LogExplorerController::class, 'index'])->name('projects.logs.index');
    Route::get('/projects/{project}/logs/{log}', [LogExplorerController::class, 'show'])->name('projects.logs.show');
    Route::post('/projects/{project}/logs/{log}/analyze', [LogExplorerController::class, 'analyze'])->name('projects.logs.analyze');
    Route::delete('/projects/{project}/logs/{log}', [LogExplorerController::class, 'destroy'])->name('projects.logs.destroy');

    // Failing Controllers
    Route::get('/projects/{project}/failing-controllers', [FailingControllersController::class, 'index'])->name('projects.failing-controllers.index');

    // Project Settings
    Route::get('/projects/{project}/settings', [ProjectSettingsController::class, 'show'])->name('projects.settings.show');
    Route::put('/projects/{project}/settings', [ProjectSettingsController::class, 'update'])->name('projects.settings.update');
    Route::post('/projects/{project}/regenerate-key', [ProjectSettingsController::class, 'regenerateKey'])->name('projects.regenerate-key');
    Route::post('/projects/{project}/regenerate-webhook-secret', [ProjectSettingsController::class, 'regenerateWebhookSecret'])->name('projects.regenerate-webhook-secret');
    Route::post('/projects/{project}/test-webhook', [ProjectSettingsController::class, 'testWebhook'])->name('projects.test-webhook');
    Route::post('/projects/{project}/truncate-logs', [ProjectSettingsController::class, 'truncateLogs'])->name('projects.truncate-logs');
    Route::delete('/projects/{project}', [ProjectSettingsController::class, 'destroy'])->name('projects.destroy');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Administration (Admin only)
    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
