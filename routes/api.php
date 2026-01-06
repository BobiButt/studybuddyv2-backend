<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('auth')->group(function () {
    // Social login routes
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);

});
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store']);


Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1');

 Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    
              


//                 Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
//         ->name('verification.verify');
    
//     Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
//         ->name('verification.send');
// });