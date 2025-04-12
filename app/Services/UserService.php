<?php

namespace App\Services;

use App\Helpers\NotificationHelper;
use App\Mail\OtpMail;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserService
{
    protected $UserRepository;

    public function __construct(UserRepository $UserRepository)
    {
        $this->UserRepository = $UserRepository;
    }

    public function all()
    {
        try {
            return $this->UserRepository->all();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function find($id)
    {
        try {
            return $this->UserRepository->find($id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create(array $data)
    {
        try {
            $user = $this->UserRepository->create($data);
            Mail::to($user->email)->send(new OtpMail($user->otp));
            return $user;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function verifyOtp(array $data): ?User
    {
        try {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                throw new Exception('User not found.');
            }
            if ($user->otp !== $data['otp']) {
                throw new Exception('Invalid OTP.');
            }
            $user->otp = null;
            $user->otp_verified = true;
            $user->save();
            return $user;
        } catch (Exception $e) {
            Log::error('OTP verification error: ' . $e->getMessage());
            throw new Exception('OTP verification failed.' . $e->getMessage());
        }
    }
    public function login(array $data): ?User
    {
        try {
            $user = $this->UserRepository->findByEmail($data['email']);

            if (!$user) {
                throw new Exception('User not found.');
            }
            if (!$user->otp_verified) {
                throw new Exception('OTP verification required.');
            }
            if (!Hash::check($data['password'], $user->password)) {
                throw new Exception('Invalid password.');
            }
            // NotificationHelper::sendNotification(
            //     $user->id,
            //     $user->id,
            //     'login',
            //     null,
            //     "You have logged in successfully."
            // );
            $user->profile_picture = asset('storage/' . $user->profile_picture);

            return $user;
        } catch (Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            throw new Exception('Login failed ' . $e->getMessage());
        }
    }
    public function resendOtp(string $email): ?User
    {
        try {
            $user = $this->UserRepository->findByEmail($email);
            $user->otp = rand(100000, 999999);
            $user->save();
            Mail::to($user->email)->send(new OtpMail($user->otp));
            return $user;
        } catch (Exception $e) {
            Log::error('Resend OTP error: ' . $e->getMessage());
            throw new Exception('Resend OTP failed.');
        }
    }
    public function changePassword(string $oldPassword, string $newPassword): ?User
    {
        try {
            $Authuser = Auth::user();
            $user = $this->UserRepository->changePassword($oldPassword, $newPassword, $Authuser->id);
            return $user;
        } catch (Exception $e) {
            Log::error('Change password error: ' . $e->getMessage());
            throw new Exception('Change password failed. ' . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        try {

            $user = $this->UserRepository->update($id, $data);
            return $user;
        } catch (Exception $e) {
            Log::error('Update user error: ' . $e->getMessage());
            throw new Exception('Update user failed. ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        return $this->UserRepository->delete($id);
    }
    public function viewProfile($userId)
    {
        try {
            return $this->UserRepository->viewprofile($userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getUserManagementData()
    {
        try {
            return $this->UserRepository->getUserManagementData();
        } catch (Exception $e) {
            Log::error('Error in getting user management data: ' . $e->getMessage());
            throw new Exception('Error in getting user management data ' . $e->getMessage());
        }
    }
    public function getAllUsers()
    {
        try {
            return $this->UserRepository->getAllUsers();
        } catch (Exception $e) {
            Log::error('Error in getting all users: ' . $e->getMessage());
            throw new Exception('Error in getting all users ' . $e->getMessage());
        }
    }
}
