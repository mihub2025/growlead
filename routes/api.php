<?php

use App\Http\Controllers\Api\Mobile\V1\AuthController;
use App\Http\Controllers\Api\Mobile\V1\CampaignController;
use App\Http\Controllers\Api\Mobile\V1\DashboardController;
use App\Http\Controllers\Api\Mobile\V1\DeviceController;
use App\Http\Controllers\Api\Mobile\V1\LeadController;
use App\Http\Controllers\Api\Mobile\V1\MeController;
use App\Http\Controllers\Api\Mobile\V1\NotificationController;
use App\Http\Controllers\Api\Mobile\V1\ReferenceController;
use App\Http\Controllers\Api\Mobile\V1\TaskController;
use App\Http\Controllers\Api\WebhookLeadController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')->post('/v1/leads/webhook/{source}', [WebhookLeadController::class, 'store']);

Route::prefix('mobile/v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [MeController::class, 'show']);
        Route::post('/me/password', [MeController::class, 'password']);
        Route::get('/dashboard', [DashboardController::class, 'show']);

        Route::post('/devices', [DeviceController::class, 'store']);
        Route::delete('/devices', [DeviceController::class, 'destroy']);

        Route::get('/leads', [LeadController::class, 'index']);
        Route::post('/leads', [LeadController::class, 'store']);
        Route::get('/leads/filters', [LeadController::class, 'filters']);
        Route::get('/leads/{lead}', [LeadController::class, 'show']);
        Route::get('/leads/{lead}/activities', [LeadController::class, 'activities']);
        Route::post('/leads/{lead}/activities', [LeadController::class, 'storeActivity']);
        Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote']);
        Route::post('/leads/{lead}/follow-ups', [LeadController::class, 'storeFollowUp']);

        Route::get('/campaigns', [CampaignController::class, 'index']);
        Route::get('/campaigns/{campaign}/next-call', [CampaignController::class, 'nextCall']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);

        Route::get('/lead-statuses', [ReferenceController::class, 'leadStatuses']);
        Route::get('/activity-types', [ReferenceController::class, 'activityTypes']);
        Route::get('/task-types', [ReferenceController::class, 'taskTypes']);
    });
});
