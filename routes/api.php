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
    Route::get('/admin/list', [App\Http\Controllers\AdminWorkflowController::class, 'listStudents']);

    Route::get('/admin/items', [App\Http\Controllers\AdminWorkflowController::class, 'indexLostFoundItems']);
    Route::get('/admin/dashboard', [App\Http\Controllers\AdminWorkflowController::class, 'dashboardMetrics']);
    Route::get('/admin/completed-returns', [App\Http\Controllers\AdminWorkflowController::class, 'indexCompletedReturns']);
    Route::get('/admin/point-rules', [App\Http\Controllers\AdminWorkflowController::class, 'indexPointRules']);
    Route::post('/admin/point-rules', [App\Http\Controllers\AdminWorkflowController::class, 'storePointRule']);
    Route::put('/admin/point-rules/{id}', [App\Http\Controllers\AdminWorkflowController::class, 'updatePointRule']);
    Route::delete('/admin/point-rules/{id}', [App\Http\Controllers\AdminWorkflowController::class, 'destroyPointRule']);
    Route::get('/admin/points', [App\Http\Controllers\AdminWorkflowController::class, 'indexPointBalances']);
    Route::post('/admin/notifications/{id}/mark-read', [App\Http\Controllers\AdminWorkflowController::class, 'markAdminNotificationRead']);

    Route::get('/admin/notifications', [App\Http\Controllers\AdminWorkflowController::class, 'indexAdminNotifications']);

    Route::post('/admin/reports/{id}/approve', [App\Http\Controllers\AdminWorkflowController::class, 'approveLostReport']);
    Route::post('/admin/reports/{id}/reject', [App\Http\Controllers\AdminWorkflowController::class, 'rejectLostReport']);
    Route::post('/admin/reports/{id}/request-correction', [App\Http\Controllers\AdminWorkflowController::class, 'requestLostReportCorrection']);

    Route::post('/admin/surrenders/{id}/receive', [App\Http\Controllers\AdminWorkflowController::class, 'receiveSurrenderRequest']);
    Route::post('/admin/surrenders/{id}/reject', [App\Http\Controllers\AdminWorkflowController::class, 'rejectSurrenderRequest']);
    Route::post('/admin/surrenders/{id}/overdue', [App\Http\Controllers\AdminWorkflowController::class, 'markSurrenderOverdue']);

    Route::post('/admin/verifications/{id}/verify', [App\Http\Controllers\AdminWorkflowController::class, 'verifyItem']);
    Route::post('/admin/verifications/{id}/reject', [App\Http\Controllers\AdminWorkflowController::class, 'rejectVerification']);
    Route::post('/admin/verifications/{id}/request-details', [App\Http\Controllers\AdminWorkflowController::class, 'requestVerificationDetails']);

    Route::put('/admin/matches/{id}/confirm', [App\Http\Controllers\AdminWorkflowController::class, 'confirmPotentialMatch']);
    Route::put('/admin/matches/{id}/reject', [App\Http\Controllers\AdminWorkflowController::class, 'rejectPotentialMatch']);

    Route::post('/admin/claims/{id}/approve', [App\Http\Controllers\AdminWorkflowController::class, 'approveClaim']);
    Route::post('/admin/claims/{id}/reject', [App\Http\Controllers\AdminWorkflowController::class, 'rejectClaim']);
    Route::post('/admin/claims/{id}/request-information', [App\Http\Controllers\AdminWorkflowController::class, 'requestClaimInformation']);

    Route::post('/admin/releases/{id}/complete', [App\Http\Controllers\AdminWorkflowController::class, 'completeItemRelease']);

    Route::post('/admin/event-fines/{id}/approve-conversion', [App\Http\Controllers\AdminWorkflowController::class, 'approveEventFineConversion']);
    Route::post('/admin/event-fines/{id}/reject-conversion', [App\Http\Controllers\AdminWorkflowController::class, 'rejectEventFineConversion']);

    Route::get('/admin/claims', [App\Http\Controllers\AdminWorkflowController::class, 'indexClaimReviews']);
    Route::get('/admin/verifications', [App\Http\Controllers\AdminWorkflowController::class, 'indexItemVerifications']);
    Route::get('/admin/releases', [App\Http\Controllers\AdminWorkflowController::class, 'indexItemReleases']);
    Route::get('/admin/matches', [App\Http\Controllers\AdminWorkflowController::class, 'indexPotentialMatches']);
    Route::get('/admin/surrenders', [App\Http\Controllers\AdminWorkflowController::class, 'indexSurrenderRequests']);
    Route::get('/admin/event-fines', [App\Http\Controllers\AdminWorkflowController::class, 'indexEventFines']);
    Route::get('/admin/settings', [App\Http\Controllers\AdminWorkflowController::class, 'indexAdminSettings']);
    Route::post('/admin/settings', [App\Http\Controllers\AdminWorkflowController::class, 'storeAdminSetting']);

    Route::get('/admin/profile', [AdminProfileController::class, 'apiProfile']);
    Route::put('/admin/profile-info', [AdminProfileController::class, 'adminEditProfile']);
    Route::put('/admin/profile-change-pass', [AdminProfileController::class, 'adminChangePass']);
    Route::post('/admin/profile-picture', [AdminProfileController::class, 'adminChangeProfile']);
    Route::post('/admin/logout', [AdminProfileController::class, 'adminLogout']);
});
