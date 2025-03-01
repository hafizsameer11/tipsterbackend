<?php

namespace App\Services;

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
        return $this->UserRepository->all();
    }

    public function find($id)
    {
        return $this->UserRepository->find($id);
    }

    public function create(array $data)
    {
       try{
        return $this->UserRepository->create($data);
       }catch(Exception $e){
           throw new Exception($e->getMessage());
       }
    }
    public function verifyOtp(array $data): ?User
    {
        try {
            $user = $this->UserRepository->findByEmail($data['email']);
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
            throw new Exception('OTP verification failed.');
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
        return $this->UserRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->UserRepository->delete($id);
    }

}
