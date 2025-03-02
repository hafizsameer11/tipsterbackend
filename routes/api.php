<?php

use App\Http\Controllers\BettingCompanyController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RankingFaqController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\FollowController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/optimize-app', function () {
    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    Artisan::call('optimize');

    return "Application optimized and caches cleared successfully!";
});
Route::get('/migrate', function () {
    Artisan::call('migrate');
    return response()->json(['message' => 'Migration successful'], 200);
});
Route::get('/migrate/rollback', function () {
    Artisan::call('migrate:rollback');
    return response()->json(['message' => 'Migration rollback successfully'], 200);
});

Route::get('/unath', function () {
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/otp-verification', [AuthController::class, 'otpVerification']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

    Route::post('/forget-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-forget-password-otp', [AuthController::class, 'verifyForgetPasswordOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']); 
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('betting-company')->group(function () {
        Route::get('/get-all', [BettingCompanyController::class, 'getAll']);
        Route::get('/get-single/{id}', [BettingCompanyController::class, 'getOne']);
        Route::post('/create', [BettingCompanyController::class, 'create']);
        Route::put('/update/{id}', [BettingCompanyController::class, 'update']);
        Route::delete('/delete/{id}', [BettingCompanyController::class, 'delete']);
    });
    Route::prefix('tip')->group(function () {
        Route::post('/create', [TipController::class, 'create']);
        Route::get('/get-all-of-user', [TipController::class, 'getFreeTipofUser']);
        Route::get('/get-all-free-running-tips', [TipController::class, 'getAllRunningTips']);
        Route::get('/approve-tip/{id}', [TipController::class, 'approveTip']);
        Route::post('/set-tip-result/{id}', [TipController::class, 'setTipResult']);
    });
    Route::prefix('ranking')->group(function () {
        Route::get('/get-user-ranking', [RankingController::class, 'getUserRanking']);
        Route::get('/get-top-30-rankings', [RankingController::class, 'getTop30Rankings']);
    });
    Route::prefix('rankingFaq')->group(function () {
        Route::get('/get-all', [RankingFaqController::class, 'getAll']);
        Route::post('/create', [RankingFaqController::class, 'create']);
        Route::put('/update/{id}', [RankingFaqController::class, 'update']);
        Route::delete('/delete/{id}', [RankingFaqController::class, 'delete']);
    });
    Route::prefix('user')->group(function () {
        Route::get('/view-profile/{userId}', [UserController::class, 'viewProfile']);
    });

    Route::post('/follow/{userId}', [FollowController::class, 'followUser']);
    Route::delete('/unfollow/{userId}', [FollowController::class, 'unfollowUser']);
    Route::get('/followers/{userId}', [FollowController::class, 'getUserFollowers']);
    Route::get('/following/{userId}', [FollowController::class, 'getUserFollowing']);
    Route::prefix('posts')->group(function () {
        Route::post('/create', [PostController::class, 'createPost']); // Create a post
        Route::get('/get-all', [PostController::class, 'getAllPosts']); // Get all posts
        Route::get('/user-post/{userId}', [PostController::class, 'getPostForUser']); // Get user-specific posts
        Route::get('/like/{postId}', [PostController::class, 'likePost']); // Like a post
        Route::post('/unlike/{postId}', [PostController::class, 'unlikePost']); // Unlike a post
        Route::post('/create-comment/{postId}', [PostController::class, 'addComment']); // Add a comment
    });

    Route::post('/comments/{commentId}/approve', [PostController::class, 'approveComment']); // Approve a comment
});
