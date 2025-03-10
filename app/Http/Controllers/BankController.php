<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\BankDetailRequest;
use App\Services\BankDetailService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    protected $bankService;
    public function __construct(BankDetailService $bankService)
    {
        $this->bankService = $bankService;
    }
    public function create(BankDetailRequest $request)
    {
        try {
            $data = $request->validated();
            $bank = $this->bankService->create($data);
            return ResponseHelper::success($bank, 'Bank created successfully', 201);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function getforAuthUser()
    {
        try {
            $user = Auth::user();
            $bank = $this->bankService->getByUserId($user->id);
            return ResponseHelper::success($bank, 'Bank fetched successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function getOfUser($userId)
    {
        try {
            $bank = $this->bankService->getByUserId($userId);
            return ResponseHelper::success($bank, 'Bank fetched successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
