<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OtpVerificationRequst;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ResetPasswordService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $userService;
    protected $resetPasswordService;
    protected $NotificationService;
    public function __construct(UserService $userService, ResetPasswordService $resetPasswordService, NotificationService $NotificationService)
    {
        $this->userService = $userService;
        $this->resetPasswordService = $resetPasswordService;
        $this->NotificationService = $NotificationService;
    }
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->userService->create($request->validated());

            return ResponseHelper::success($user, 'User registered successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function otpVerification(OtpVerificationRequst $request)
    {
        try {
            $user = $this->userService->verifyOtp($request->validated());
            return ResponseHelper::success($user, 'OTP verified successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function login(LoginRequest $request)
    {
        try {
            Log::info('Login Request:', $request->validated());
            $user = $this->userService->login($request->validated());
            $userd = $user['user'];


            // Log::info('User Logged In:', [
            //     'user' => $user,
            //     'request_headers' => request()->headers->all()
            // ]);
            $notification = $this->NotificationService->sendToUserById($user->id, 'Login Alert', 'You have successfully logged in to your account.');
            Log::info('Notification Response:', $notification);
            $token = $user->createToken('auth_token')->plainTextToken;

            $data = [
                'user' => $user,
                'token' => $token
            ];

            return ResponseHelper::success($data, 'User logged in successfully', 200);
        } catch (\Exception $e) {
            Log::error('Login Error:', ['error' => $e->getMessage()]);
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function resendOtp(Request $request)
    {
        try {
            $user = $this->userService->resendOtp($request->email);
            return ResponseHelper::success($user, 'OTP resent successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $user = $this->resetPasswordService->forgetPassword($request->email);
            return ResponseHelper::success($user, 'OTP resent successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function verifyForgetPasswordOtp(ResetPasswordRequest $request)
    {
        try {
            $user = $this->resetPasswordService->verifyForgetPassswordOtp($request->email, $request->otp);
            return ResponseHelper::success($user, 'OTP verified successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function resetPassword(Request $request)
    {
        try {
            $user = $this->resetPasswordService->resetPassword($request->email, $request->password);
            return ResponseHelper::success($user, 'Password reset successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $this->userService->changePassword($request->old_password, $request->new_password);
            return ResponseHelper::success($user, 'Password changed successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function testFcmNotification(Request $request)
    {
        $userId = $request->userId;
        try {
            $notification = $this->NotificationService->sendToUserById($userId, 'Test Notification. This is a test notification ', 'This is a test notification');
            return ResponseHelper::success($notification, 'notification tewst successfull', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
