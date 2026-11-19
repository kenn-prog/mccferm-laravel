<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController, FacilityController, EquipmentController, ReservationController, RequestController, TransactionController, NotificationController, ProfileController};

/*
|--------------------------------------------------------------------------
| API Routes - MCC-FERMS Mobile Application
|--------------------------------------------------------------------------
|
| These routes are for the Flutter mobile application.
| All routes return JSON responses.
| Authentication uses Laravel Sanctum.
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/mobile-auth/exchange', [AuthController::class, 'exchangeMobileTicket'])
    ->middleware('throttle:10,1');

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Facilities (Student)
    Route::get('/facilities', [FacilityController::class, 'index']);
    Route::get('/facilities/{facility}', [FacilityController::class, 'show']);
    Route::get('/facilities/{facility}/availability', [FacilityController::class, 'availability']);
    
    // Equipment (Student)
    Route::get('/equipment', [EquipmentController::class, 'index']);
    Route::get('/equipment/{equipment}', [EquipmentController::class, 'show']);
    
    // Reservations (Student)
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::patch('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    
    // Equipment Borrow Requests (Student)
    Route::post('/borrow', [RequestController::class, 'borrow']);
    Route::get('/requests', [RequestController::class, 'index']);
    Route::get('/requests/{request}', [RequestController::class, 'show']);
    Route::patch('/requests/{request}', [RequestController::class, 'update']);
    Route::delete('/requests/{request}', [RequestController::class, 'destroy']);
    
    // Transactions (Student)
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    
    // Notifications (Student)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/mark-read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markSingleRead']);
    
    // Profile (Student)
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    
    // Admin/Staff Routes
    Route::middleware('role:staff,admin,superadmin')->prefix('admin')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Api\AdminController::class, 'dashboard']);
        
        // Request Management
        Route::get('/requests', [App\Http\Controllers\Api\AdminController::class, 'requests']);
        Route::post('/requests/{request}/approve', [App\Http\Controllers\Api\AdminController::class, 'approve']);
        Route::post('/requests/{request}/reject', [App\Http\Controllers\Api\AdminController::class, 'reject']);
        Route::post('/requests/{request}/release', [App\Http\Controllers\Api\AdminController::class, 'release']);
        
        // Transaction Management
        Route::get('/transactions', [App\Http\Controllers\Api\AdminController::class, 'transactions']);
        Route::post('/transactions/{transaction}/return', [App\Http\Controllers\Api\AdminController::class, 'markReturned']);
        Route::post('/transactions/{transaction}/remind', [App\Http\Controllers\Api\AdminController::class, 'sendReminder']);
        
        // Facility Management
        Route::get('/facilities', [App\Http\Controllers\Api\AdminController::class, 'facilities']);
        Route::post('/facilities', [App\Http\Controllers\Api\AdminController::class, 'storeFacility']);
        Route::put('/facilities/{facility}', [App\Http\Controllers\Api\AdminController::class, 'updateFacility']);
        Route::delete('/facilities/{facility}', [App\Http\Controllers\Api\AdminController::class, 'deleteFacility']);
        
        // Equipment Management
        Route::get('/equipment', [App\Http\Controllers\Api\AdminController::class, 'equipment']);
        Route::post('/equipment', [App\Http\Controllers\Api\AdminController::class, 'storeEquipment']);
        Route::put('/equipment/{equipment}', [App\Http\Controllers\Api\AdminController::class, 'updateEquipment']);
        Route::delete('/equipment/{equipment}', [App\Http\Controllers\Api\AdminController::class, 'deleteEquipment']);
        
        // User Management (admin only)
        Route::middleware('role:admin,superadmin')->group(function () {
            Route::get('/users', [App\Http\Controllers\Api\AdminController::class, 'users']);
            Route::put('/users/{user}', [App\Http\Controllers\Api\AdminController::class, 'updateUser']);
        });
    });
});
