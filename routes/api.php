<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MinistryController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\ProgramKerjaController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\KasController;

Route::prefix('v1')->group(function () {
    // Public routes (no authentication required)
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected routes (require authentication)
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // Dashboard statistics
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
        
        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/types', [ActivityLogController::class, 'types']);
        Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show']);
        
        // Roles CRUD
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/permissions', [RoleController::class, 'permissions']);
        Route::get('/roles/{id}', [RoleController::class, 'show']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{id}', [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
        
        // Users CRUD
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Ministries CRUD
        Route::get('/ministries', [MinistryController::class, 'index']);
        Route::get('/ministries/{id}', [MinistryController::class, 'show']);
        Route::post('/ministries', [MinistryController::class, 'store']);
        Route::put('/ministries/{id}', [MinistryController::class, 'update']);
        Route::delete('/ministries/{id}', [MinistryController::class, 'destroy']);
        
        // Proposals CRUD
        Route::get('/proposals', [ProposalController::class, 'index']);
        Route::get('/proposals/statuses', [ProposalController::class, 'statuses']);
        Route::get('/proposals/{id}', [ProposalController::class, 'show']);
        Route::post('/proposals', [ProposalController::class, 'store']);
        Route::put('/proposals/{id}', [ProposalController::class, 'update']);
        Route::patch('/proposals/{id}/status', [ProposalController::class, 'updateStatus']);
        Route::delete('/proposals/{id}', [ProposalController::class, 'destroy']);
        
        // Program Kerja CRUD
        Route::get('/program-kerja', [ProgramKerjaController::class, 'index']);
        Route::get('/program-kerja/statuses', [ProgramKerjaController::class, 'statuses']);
        Route::get('/program-kerja/{id}', [ProgramKerjaController::class, 'show']);
        Route::post('/program-kerja', [ProgramKerjaController::class, 'store']);
        Route::put('/program-kerja/{id}', [ProgramKerjaController::class, 'update']);
        Route::delete('/program-kerja/{id}', [ProgramKerjaController::class, 'destroy']);
        
        // Announcements
        Route::apiResource('announcements', AnnouncementController::class);
        Route::get('/announcements/unread-count', [AnnouncementController::class, 'unreadCount']);
        
        // Device Token
        Route::post('/device-token', [DeviceTokenController::class, 'store']);
        Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);
        
        // Kas Internal
        Route::prefix('kas')->group(function () {
            Route::get('/current', [KasController::class, 'current']);
            Route::get('/history', [KasController::class, 'history']);
            Route::post('/pay', [KasController::class, 'pay']);
        });
    });
    
    // Midtrans callback (no auth required)
    Route::post('/kas/callback', [KasController::class, 'callback']);
});
