<?php

use App\Http\Controllers\Api\V1\AdminPropertyController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\OwnerPhoneRequestController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\PostViewController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Test/Ping route (No middleware for easy browser check)
Route::get('/ping', function () {
    return response()->json(['message' => 'Pong!', 'time' => now()]);
});

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    // =========================
    // Public routes (Auth)
    // =========================
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('/', function () {
            return response()->json([
                'message' => 'Welcome to API v1',
                'status' => 'running',
                'docs' => url('/docs/api'),
            ]);
        });

        // =========================
        // Public Dictionaries
        // =========================
        Route::prefix('dicts')->group(function () {
            Route::get('/areas', [\App\Http\Controllers\Api\V1\AreaController::class, 'index']);
            Route::get('/categories', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);
            Route::get('/projects', [\App\Http\Controllers\Api\V1\ProjectController::class, 'index']);
            Route::get('/property-options', [\App\Http\Controllers\Api\V1\DictPropertyController::class, 'options']);
        });

        // Tải file: Chấp nhận API Key (để xem ảnh/tải file trực tiếp trên web/app)
        // Việc check quyền hoặc Mask dữ liệu PRIVATE sẽ được xử lý trong Controller
        Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');

        // =========================
        // Protected routes
        // =========================
        Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserNotLocked::class])->group(function () {
            Route::post('/auth/refresh', [AuthController::class, 'refresh']);
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

            // Email APIs
            Route::prefix('emails')->group(function () {
                Route::post('/send-otp', [\App\Http\Controllers\Api\V1\EmailController::class, 'sendOtp']);
                Route::post('/property-approved', [\App\Http\Controllers\Api\V1\EmailController::class, 'sendPropertyApproved']);
                Route::post('/property-rejected', [\App\Http\Controllers\Api\V1\EmailController::class, 'sendPropertyRejected']);
                Route::post('/phone-request-approved', [\App\Http\Controllers\Api\V1\EmailController::class, 'sendPhoneRequestApproved']);
                Route::post('/custom', [\App\Http\Controllers\Api\V1\EmailController::class, 'sendCustomEmail']);
            });

            // Me
            Route::get('/me', function (Request $request) {
                return new \App\Http\Resources\UserResource($request->user());
            });
            Route::post('/me', [AuthController::class, 'updateProfile']); 

            Route::get('/me/properties', [PropertyController::class, 'me']);
            Route::get('/me/posts', [PostController::class, 'me']);
            Route::get('/me/posts/{post}', [PostController::class, 'meShow']);
            Route::get('/me/files', [FileController::class, 'me']);
            Route::get('/me/owner-phone-requests', [OwnerPhoneRequestController::class, 'myRequests']);
            Route::get('/me/post-history', [PostViewController::class, 'history']);
            Route::delete('/me/post-history', [PostViewController::class, 'clearHistory']);

            // Upload & Manage Files
            Route::prefix('files')->group(function () {
                Route::get('/', [FileController::class, 'index']);
                Route::post('/', [FileController::class, 'store']);
                Route::post('/multiple', [FileController::class, 'storeMultiple']);
                Route::put('/reorder', [FileController::class, 'reorder']);
                Route::post('/{file}/set-primary', [FileController::class, 'setPrimary']);
                Route::delete('/{file}', [FileController::class, 'destroy']);
            });

            // Properties
            Route::get('/properties/map', [PropertyController::class, 'map']);
            Route::get('/properties', [PropertyController::class, 'index']);
            Route::post('/properties', [PropertyController::class, 'store']);
            Route::get('/properties/{property}', [PropertyController::class, 'show']);
            Route::put('/properties/{property}', [PropertyController::class, 'update']);
            Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);
            Route::post('/properties/{property}/owner-phone-requests', [OwnerPhoneRequestController::class, 'store']);

            // Posts
            Route::get('/posts', [PostController::class, 'index']);
            Route::get('/posts/{post}', [PostController::class, 'show']);
            Route::post('/posts', [PostController::class, 'store']);
            Route::patch('/posts/{post}', [PostController::class, 'update']);
            Route::delete('/posts/{post}', [PostController::class, 'destroy']);
            Route::post('/posts/{post}/owner-phone-requests', [OwnerPhoneRequestController::class, 'storeByPost']);
            Route::post('/posts/{post}/renew', [PostController::class, 'renew']);
            Route::post('/posts/{post}/hide', [PostController::class, 'hide']);
            Route::post('/posts/{post}/view', [PostViewController::class, 'record'])->middleware('throttle:30,1');

            // Reports
            Route::post('/reports', [ReportController::class, 'store']);

            // =========================
            // Admin Routes
            // =========================
            Route::prefix('admin')->middleware(['role:SUPER_ADMIN|OFFICE_ADMIN'])->group(function () {
                // Properties
                Route::get('/properties', [AdminPropertyController::class, 'index']);
                Route::post('/properties/{property}/approve', [AdminPropertyController::class, 'approve']);
                Route::post('/properties/{property}/reject', [AdminPropertyController::class, 'reject']);

                // Owner Phone Requests
                Route::get('/owner-phone-requests', [OwnerPhoneRequestController::class, 'index']);
                Route::post('/owner-phone-requests/{ownerPhoneRequest}/approve', [OwnerPhoneRequestController::class, 'approve']);
                Route::post('/owner-phone-requests/{ownerPhoneRequest}/reject', [OwnerPhoneRequestController::class, 'reject']);

                // Reports
                Route::get('/reports', [ReportController::class, 'index']);
                Route::post('/reports/{report}/resolve', [ReportController::class, 'resolve']);

                // Users
                Route::apiResource('users', AdminUserController::class);
                Route::post('/users/{user}/lock', [AdminUserController::class, 'lock']);
                Route::post('/users/{user}/unlock', [AdminUserController::class, 'unlock']);

                // Audit Logs
                Route::get('/audit-logs', [AuditLogController::class, 'index']);

                // Dictionary CRUD (admin only)
                Route::post('/areas', [\App\Http\Controllers\Api\V1\AreaController::class, 'store']);
                Route::put('/areas/{area}', [\App\Http\Controllers\Api\V1\AreaController::class, 'update']);
                Route::post('/categories', [\App\Http\Controllers\Api\V1\CategoryController::class, 'store']);
                Route::put('/categories/{category}', [\App\Http\Controllers\Api\V1\CategoryController::class, 'update']);
                Route::post('/projects', [\App\Http\Controllers\Api\V1\ProjectController::class, 'store']);
                Route::put('/projects/{project}', [\App\Http\Controllers\Api\V1\ProjectController::class, 'update']);
            });
        });
    });
});
