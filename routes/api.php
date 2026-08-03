<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\StudentLoginController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\StudentNotificationController;
/*
|----------------------------------------------------------------------
| PUBLIC ROUTES
|----------------------------------------------------------------------
*/

Route::get('/test-me', fn() => response()->json(['status' => 'success']));

// Auth
Route::post('/login', [StudentLoginController::class, 'login']);
Route::post('/register', [StudentLoginController::class, 'register']);

// Admin Login Requests
Route::post('/admin/login/request', [AdminAuthController::class, 'loginRequest']);

/*
|----------------------------------------------------------------------
| AUTHENTICATED ROUTES
|----------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Current User
    Route::get('/user', fn(Request $request) => $request->user());

    Route::prefix('student')->group(function () {
        Route::get('/profile', [StudentProfileController::class, 'apiProfile']);
        Route::put('/profile', [StudentProfileController::class, 'apiUpdateProfile']);
        Route::post('/profile/picture', [StudentProfileController::class, 'updateProfilePicture']);
        Route::post('/profile/change-password', [StudentProfileController::class, 'apiChangePassword']);
        Route::get('/notifications', [StudentNotificationController::class, 'apiNotifications']);
        Route::delete('/notifications/{id}', [StudentNotificationController::class, 'deleteStudentNotification']);
        Route::put('/notifications/{id}/mark-read', [StudentNotificationController::class, 'markStudentNotificationRead']);
    });

    // Lost and Found Reports
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index']);
    Route::get('/reports/mine', [App\Http\Controllers\ReportController::class, 'mine']);
    Route::get('/reports/{id}', [App\Http\Controllers\ReportController::class, 'show']);
    Route::post('/reports', [App\Http\Controllers\ReportController::class, 'store']);
    Route::get('/reports/{id}/matches', [App\Http\Controllers\ReportController::class, 'matches']);
    Route::post('/reports/{id}/matches/{matchId}/confirm', [App\Http\Controllers\ReportController::class, 'confirmMatch']);

    Route::post('/device-tokens', [App\Http\Controllers\DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [App\Http\Controllers\DeviceTokenController::class, 'destroy']);

    Route::post('/logout', [StudentLoginController::class, 'logout']);
});

/*
|----------------------------------------------------------------------
| ADMIN ROUTES
|----------------------------------------------------------------------
*/

Route::middleware('auth:admin')->group(function () {
    Route::post('/admin/students', [AdminAuthController::class, 'storeStudentRecord']);
    Route::put('/admin/students/{id}', [AdminAuthController::class, 'updateStudentRecord']);
    Route::delete('/admin/students/{id}', [AdminAuthController::class, 'deleteStudentRecord']);

    Route::get('/admin/profile', [AdminProfileController::class, 'apiProfile']);
    Route::put('/admin/profile-info', [AdminProfileController::class, 'adminEditProfile']);
    Route::put('/admin/profile-change-pass', [AdminProfileController::class, 'adminChangePass']);
    Route::post('/admin/profile-picture', [AdminProfileController::class, 'adminChangeProfile']);
    Route::post('/admin/logout', [AdminProfileController::class, 'adminLogout']);
});
