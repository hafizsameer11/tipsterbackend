<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\BettingCompanyController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardData;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostShareController;
use App\Http\Controllers\PrivacyPageController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RankingFaqController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\FollowController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSubscriptionController;
use App\Http\Controllers\VideoController;
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
Route::get('admin/user/getAllUsers', [UserController::class, 'getAllUsers']);
Route::get('privacy/get',[PrivacyPageController::class,'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('betting-company')->group(function () {
        Route::get('/get-all', [BettingCompanyController::class, 'getAll']);
        // Route::put('/get-all/{id}', [BettingCompanyController::class, 'update']);
        Route::get('/get-single/{id}', [BettingCompanyController::class, 'getOne']);
        Route::post('/create', [BettingCompanyController::class, 'create']);
        Route::post('/update/{id}', [BettingCompanyController::class, 'update']);
        Route::delete('/delete/{id}', [BettingCompanyController::class, 'delete']);
    });
    Route::prefix('tip')->group(function () {
        Route::post('/create', [TipController::class, 'create']);
        Route::get('/get-all-of-user', [TipController::class, 'getFreeTipofUser']);
        Route::get('/get-all-free-running-tips', [TipController::class, 'getAllRunningTips']);
        Route::get('/get-all-vip-running-tips', [TipController::class, 'getAllVipTips']);
        Route::get('/approve-tip/{id}', [TipController::class, 'approveTip']);
        Route::post('/set-tip-result/{id}', [TipController::class, 'setTipResult']);
    });
    Route::prefix('ranking')->group(function () {
        Route::get('/get-user-ranking', [RankingController::class, 'getUserRanking']);
        Route::get('/get-top-30-rankings', [RankingController::class, 'getTop30Rankings']);
    });
    Route::prefix('Faq')->group(function () {
        Route::get('/get-all/{type}', [RankingFaqController::class, 'getAllbyType']);
        Route::post('/create', [RankingFaqController::class, 'create']);
        Route::put('/update/{id}', [RankingFaqController::class, 'update']);
        Route::delete('/delete/{id}', [RankingFaqController::class, 'delete']);
        Route::get('get', [RankingFaqController::class, 'getll']);
    });
    Route::prefix('user')->group(function () {
        Route::get('/view-profile/{userId}', [UserController::class, 'viewProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/delete', [UserController::class, 'delete']);
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
        Route::post('/create-comment', [PostController::class, 'addComment']); // Add a comment
        Route::get('/delete-post/{postId}', [PostController::class, 'deletePost']); // Get comments for a post});
        Route::get('approvePost/{postId}', [PostController::class, 'approvePost']);
    });
    Route::prefix('bank')->group(function () {
        Route::post('/create', [BankController::class, 'create']);
        Route::get('/get-for-user', [BankController::class, 'getforAuthUser']);
        Route::get('/get-of-user/{id}', [BankController::class, 'getOfUser']);
    });
    Route::post('/create-package', [SubscriptionController::class, 'createPackage']);
    Route::get('/packages', [SubscriptionController::class, 'getAllPackage']);
    Route::get('/transactions', [SubscriptionController::class, 'getTransaction']);
    Route::post('/subscriptions', [SubscriptionController::class, 'storePurchase']);
    Route::post('/subscriptions/finish', [SubscriptionController::class, 'finishSubscription']);
    Route::post('/comments/approve/{commentId}', [PostController::class, 'approveComment']);
    Route::post('/comments/delete/{commentId}', [PostController::class, 'deleteComment']);


    Route::post('privacy/create',[PrivacyPageController::class,'create']);


    Route::post('/user/update-profile/{userId}', [UserController::class, 'updateProfile']);

    Route::prefix('chat')->group(function () {
        Route::post('/start', [ChatController::class, 'createChat']); // Start a chat
        Route::get('/{userId}', [ChatController::class, 'getUserChats']); // Get user chats
        Route::post('/close/{chatId}', [ChatController::class, 'closeChat']); // Close a chat
    });

    Route::prefix('message')->group(function () {
        Route::post('/send', [MessageController::class, 'sendMessage']); // Send a message
        Route::get('/{chatId}', [MessageController::class, 'getChatMessages']); // Get chat messages
    });
    Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
    Route::get('/notifications/un-read', [NotificationController::class, 'getUnreadNotificationCount']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    //admin route
    Route::post('/subscribe', [UserSubscriptionController::class, 'subscribe']); // Subscribe to a user
    Route::post('/unsubscribe', [UserSubscriptionController::class, 'unsubscribe']); // Unsubscribe from a user
    Route::get('/user/{userId}/subscriptions', [UserSubscriptionController::class, 'getUserSubscriptions']); // Get user's subscriptions
    Route::get('/user/{userId}/subscribers', [UserSubscriptionController::class, 'getSubscribers']); // Get user's subscribers

    Route::post('/posts/share', [PostShareController::class, 'sharePost']); // Share a post
    Route::get('/posts/{postId}/shares', [PostShareController::class, 'getShares']); // Get post shares
    Route::prefix('admin')->group(function () {
        Route::get('/get-user-management-data', [UserController::class, 'getUserManagementData']);
        Route::get('/user/{userId}', [UserController::class, 'userDetails']);

        Route::get('/get-post-detail/{id}', [PostController::class, 'getPostDetail']);
        Route::post('/tip/update/{tipId}', [TipController::class, 'updateTip']); //only status and result
        Route::get('/tip/get-all', [TipController::class, 'getAllTips']);
        //rank management
        Route::get('rank/get-top-10-rankings', [RankingController::class, 'getTop10Rankings']);
        Route::post('rank/update-winner-amount', [RankingController::class, 'updateWinnersAmounts']);
        Route::get('rank/get-winners-amount', [RankingController::class, 'getWinnersAmount']);
        Route::get('rank/get-winners-amount-by-rank/{rank}', [RankingController::class, 'getWinnersAmountByRank']);
        Route::post('rank/pay-rank-amount', [RankingController::class, 'payRankingPayment']);
        Route::post('notifications/create', [NotificationController::class, 'createNotificationForUsers']);
        Route::get('notifications/get', [NotificationController::class, 'getAdminNotifications']);

        Route::get('get-post-management-data', [PostController::class, 'getPostManagemtnData']);
        Route::get('make-post-pin/{id}', [PostController::class, 'makePostPin']);
        Route::get('get-app-actiity', [NotificationController::class, 'getAllUserActivity']);
        Route::get('get-chats-for-admin', [MessageController::class, 'getChatsForAdmin']);
        Route::get('get-messages-for-admin/{chatId}', [MessageController::class, 'getMessagesForAdmin']);
        Route::post('send-messages-for-admin', [MessageController::class, 'sendMessageforAdmin']);

        Route::get('get-dashboard-data', [DashboardData::class, 'getDashboardData']);

        Route::post('store-vide', [VideoController::class, 'storeVideo']);
        Route::get('get-videos', [VideoController::class, 'index']);
        Route::get('/get-subscriptions',[SubscriptionController::class,'getSubscriptions']);
    });
});
