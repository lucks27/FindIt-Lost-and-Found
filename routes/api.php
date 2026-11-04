<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AdminWorkflowController;
use App\Http\Controllers\AdminStudentPointAdjustmentController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\ItemReleaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentLoginController;
use App\Http\Controllers\StudentNotificationController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentFinePaymentController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/test-me', fn() => response()->json(['status' => 'success']));

// Public, read-only category names/IDs. Only admins can change point rules.
// Mobile report forms refresh this list whenever the category chooser opens.
Route::get('/categories', [ReportController::class, 'categories']);

// Student authentication
Route::post('/login', [StudentLoginController::class, 'login']);
Route::post('/login/google', [StudentLoginController::class, 'googleLogin']);
Route::middleware('web')->post('/register', [StudentLoginController::class, 'register']);

// Admin login request
Route::middleware('web')->post('/admin/login/request', [AdminAuthController::class, 'loginRequest']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Current user
    Route::get('/user', fn(Request $request) => $request->user());

    Route::prefix('student')->group(function () {

        // Student account & profile
        Route::get('/profile', [StudentProfileController::class, 'apiProfile']);
        Route::get('/recognition', [StudentProfileController::class, 'apiRecognition']);

        // Student-initiated recognition-point payment requests; admin still approves.
        Route::get('/payment-requests', [StudentFinePaymentController::class, 'index']);
        Route::post('/payment-requests', [StudentFinePaymentController::class, 'store']);
        Route::put('/profile', [StudentProfileController::class, 'apiUpdateProfile']);
        Route::post('/profile/picture', [StudentProfileController::class, 'updateProfilePicture']);
        Route::post('/profile/change-password', [StudentProfileController::class, 'apiChangePassword']);

        // Student notifications
        Route::get('/notifications', [StudentNotificationController::class, 'apiNotifications']);
        Route::delete('/notifications/{id}', [StudentNotificationController::class, 'deleteStudentNotification']);
        Route::put('/notifications/{id}/mark-read', [StudentNotificationController::class, 'markStudentNotificationRead']);
    });

    // Lost & found reports
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/mine', [ReportController::class, 'mine']);
    Route::get('/reports/{id}', [ReportController::class, 'show']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::put('/reports/{id}', [ReportController::class, 'update']);
    Route::post('/reports/{id}/claims', [ReportController::class, 'submitClaim']);

    // Claims
    Route::get('/claims/mine', [ReportController::class, 'myClaims']);
    Route::post('/claims/{id}/additional-information', [ReportController::class, 'submitAdditionalClaimInformation']);

    // Report photos & potential matches
    Route::post('/reports/{id}/images', [ReportController::class, 'uploadImages']);
    Route::delete('/reports/{reportId}/images/{imageId}', [ReportController::class, 'deleteImage']);
    Route::get('/reports/{id}/matches', [ReportController::class, 'matches']);
    Route::post('/reports/{id}/matches/{matchId}/confirm', [ReportController::class, 'confirmMatch']);

    // Device tokens & web push
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::post('/device-tokens/test', [DeviceTokenController::class, 'test']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);
    Route::post('/web-push/subscribe', [DeviceTokenController::class, 'storeWebPushSubscription']);
    Route::delete('/web-push/unsubscribe', [DeviceTokenController::class, 'destroyWebPushSubscription']);

    // Logout
    Route::post('/logout', [StudentLoginController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth:admin'])->group(function () {

    // Student management
    Route::post('/admin/students', [AdminAuthController::class, 'storeStudentRecord']);
    Route::put('/admin/students/{id}', [AdminAuthController::class, 'updateStudentRecord']);
    Route::patch('/admin/students/{id}/password', [AdminAuthController::class, 'updateStudentPassword']);
    Route::delete('/admin/students/{id}', [AdminAuthController::class, 'deleteStudentRecord']);
    Route::get('/admin/list', [AdminWorkflowController::class, 'listStudents']);

    // Categories & pending reports
    Route::get('/admin/categories', [AdminWorkflowController::class, 'indexItemCategories']);
    Route::get('/admin/pending-reports', [AdminWorkflowController::class, 'indexPendingReports']);

    // Archive
    Route::get('/admin/archive', [AdminWorkflowController::class, 'indexArchive']);
    Route::post('/admin/archive/{id}/restore', [AdminWorkflowController::class, 'restoreArchiveItem']);

    // Mobile posting
    Route::get('/admin/mobile-posts', [AdminWorkflowController::class, 'indexMobilePostItems']);
    Route::post('/admin/mobile-posts/{id}/publish', [AdminWorkflowController::class, 'publishMobileItem']);
    Route::patch('/admin/mobile-posts/{id}/visibility', [AdminWorkflowController::class, 'toggleMobilePostVisibility']);
    Route::post('/admin/mobile-posts/{id}/archive', [AdminWorkflowController::class, 'archiveMobilePost']);

    // Dashboard & audit logs
    Route::get('/admin/dashboard', [AdminWorkflowController::class, 'dashboardMetrics']);
    Route::get('/admin/audit-logs', [AdminWorkflowController::class, 'indexAuditLogs']);

    // Point rules & balances
    Route::get('/admin/point-rules', [AdminWorkflowController::class, 'indexPointRules']);
    Route::post('/admin/point-rules', [AdminWorkflowController::class, 'storePointRule']);
    Route::put('/admin/point-rules/{id}', [AdminWorkflowController::class, 'updatePointRule']);
    Route::delete('/admin/point-rules/{id}', [AdminWorkflowController::class, 'destroyPointRule']);
    Route::get('/admin/points', [AdminWorkflowController::class, 'indexPointBalances']);
    Route::put('/admin/points/{id}', [AdminWorkflowController::class, 'updatePointBalance']);
    Route::post('/admin/points/{id}/adjust', [AdminStudentPointAdjustmentController::class, 'store']);

    // Notifications
    Route::post('/admin/notifications/{id}/mark-read', [AdminWorkflowController::class, 'markAdminNotificationRead']);
    Route::get('/admin/notifications', [AdminWorkflowController::class, 'indexAdminNotifications']);

    // Report review actions
    Route::post('/admin/reports/{id}/approve', [AdminWorkflowController::class, 'approveLostReport']);
    Route::post('/admin/reports/{id}/reject', [AdminWorkflowController::class, 'rejectLostReport']);
    Route::post('/admin/reports/{id}/request-correction', [AdminWorkflowController::class, 'requestLostReportCorrection']);

    // Pending surrender actions
    Route::post('/admin/surrenders/{id}/receive', [AdminWorkflowController::class, 'receiveSurrenderRequest']);
    Route::post('/admin/surrenders/{id}/reject', [AdminWorkflowController::class, 'rejectSurrenderRequest']);
    Route::post('/admin/surrenders/{id}/request-details', [AdminWorkflowController::class, 'requestSurrenderDetails']);
    Route::post('/admin/surrenders/{id}/overdue', [AdminWorkflowController::class, 'markSurrenderOverdue']);

    // Item verification actions
    Route::post('/admin/verifications/{id}/verify', [AdminWorkflowController::class, 'verifyItem']);
    Route::post('/admin/verifications/{id}/reject', [AdminWorkflowController::class, 'rejectVerification']);
    Route::post('/admin/verifications/{id}/request-details', [AdminWorkflowController::class, 'requestVerificationDetails']);
    Route::post('/admin/verifications/{id}/claim-instructions', [AdminWorkflowController::class, 'sendClaimInstructions']);
    Route::post('/admin/verifications/{id}/complete', [AdminWorkflowController::class, 'completeVerificationTransaction']);
    Route::post('/admin/verifications/{id}/archive', [AdminWorkflowController::class, 'archiveVerification']);

    // Claim review actions
    Route::post('/admin/claims/{id}/approve', [AdminWorkflowController::class, 'approveClaim']);
    Route::post('/admin/claims/{id}/reject', [AdminWorkflowController::class, 'rejectClaim']);
    Route::post('/admin/claims/{id}/request-info', [AdminWorkflowController::class, 'requestClaimInformation']);

    // Item release actions
    Route::put('/admin/releases/{id}/checklist', [ItemReleaseController::class, 'updateChecklist']);
    Route::post('/admin/releases/{id}/complete', [AdminWorkflowController::class, 'completeItemRelease']);

    // Potential match actions
    Route::put('/admin/matches/{id}/confirm', [AdminWorkflowController::class, 'confirmPotentialMatch']);
    Route::put('/admin/matches/{id}/reject', [AdminWorkflowController::class, 'rejectPotentialMatch']);
    Route::put('/admin/matches/{id}/reset', [AdminWorkflowController::class, 'resetPotentialMatch']);

    // Event fines & conversions
    Route::post('/admin/event-fine-conversions', [AdminWorkflowController::class, 'storeEventFineConversion']);
    Route::post('/admin/event-fines/{id}/approve-conversion', [AdminWorkflowController::class, 'approveEventFineConversion']);
    Route::post('/admin/event-fines/{id}/reject-conversion', [AdminWorkflowController::class, 'rejectEventFineConversion']);
    Route::post('/admin/event-fines', [AdminWorkflowController::class, 'storeEventFine']);
    Route::post('/admin/event-fines/{id}/payment', [AdminWorkflowController::class, 'recordEventFinePayment']);

    // Queue listings
    Route::get('/admin/verifications', [AdminWorkflowController::class, 'indexItemVerifications']);
    Route::get('/admin/matches', [AdminWorkflowController::class, 'indexPotentialMatches']);
    Route::get('/admin/surrenders', [AdminWorkflowController::class, 'indexSurrenderRequests']);
    Route::get('/admin/event-fines', [AdminWorkflowController::class, 'indexEventFines']);

    // Fine events
    Route::get('/admin/event-fine-events', [AdminWorkflowController::class, 'indexEventFineEvents']);
    Route::post('/admin/event-fine-events', [AdminWorkflowController::class, 'storeEventFineEvent']);
    Route::get('/admin/event-fine-events/{id}', [AdminWorkflowController::class, 'showEventFineEvent']);
    Route::put('/admin/event-fine-events/{id}', [AdminWorkflowController::class, 'updateEventFineEvent']);
    Route::delete('/admin/event-fine-events/{id}', [AdminWorkflowController::class, 'deleteEventFineEvent']);

    // Claim & release listings
    Route::get('/admin/claims', [AdminWorkflowController::class, 'indexClaimReviews']);
    Route::get('/admin/releases', [ItemReleaseController::class, 'index']);

    // Settings
    Route::get('/admin/settings', [AdminWorkflowController::class, 'indexAdminSettings']);
    Route::post('/admin/settings', [AdminWorkflowController::class, 'storeAdminSetting']);

    // Admin profile & logout
    Route::get('/admin/profile', [AdminProfileController::class, 'apiProfile']);
    Route::put('/admin/profile-info', [AdminProfileController::class, 'adminEditProfile']);
    Route::put('/admin/profile-change-pass', [AdminProfileController::class, 'adminChangePass']);
    Route::post('/admin/profile-picture', [AdminProfileController::class, 'adminChangeProfile']);
    Route::post('/admin/logout', [AdminProfileController::class, 'adminLogout']);
});
