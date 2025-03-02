<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Exception;

class UserService
{
    protected $UserRepository;

    public function __construct(UserRepository $UserRepository)
    {
        $this->UserRepository = $UserRepository;
    }

    public function viewProfile($userId){
        try{
            return $this->UserRepository->viewprofile($userId);
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}