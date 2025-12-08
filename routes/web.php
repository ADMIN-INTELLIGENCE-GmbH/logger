<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FailingControllersController;
use App\Http\Controllers\LogExplorerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('projects.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/dashboard', [DashboardController::class, 'index'])->name('projects.dashboard');
    
    // Log Explorer
    Route::get('/projects/{project}/logs', [LogExplorerController::class, 'index'])->name('projects.logs.index');
    Route::get('/projects/{project}/logs/{log}', [LogExplorerController::class, 'show'])->name('projects.logs.show');
    
    // Failing Controllers
    Route::get('/projects/{project}/failing-controllers', [FailingControllersController::class, 'index'])->name('projects.failing-controllers.index');
    
    // Project Settings
    Route::get('/projects/{project}/settings', [ProjectSettingsController::class, 'show'])->name('projects.settings.show');
    Route::put('/projects/{project}/settings', [ProjectSettingsController::class, 'update'])->name('projects.settings.update');
    Route::post('/projects/{project}/regenerate-key', [ProjectSettingsController::class, 'regenerateKey'])->name('projects.regenerate-key');
    Route::delete('/projects/{project}', [ProjectSettingsController::class, 'destroy'])->name('projects.destroy');
    
    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
