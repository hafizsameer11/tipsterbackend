<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\TipRequest;
use App\Models\Tip;
use App\Services\TipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TipController extends Controller
{
    protected $tipService;
    public function __construct(TipService $tipService)
    {
        $this->tipService = $tipService;
    }
    public function getTipCountForToday(){
        $user=Auth::user();
        // $today=
        $tipCount=Tip::where('user_id',$user->id)->whereDate('created_at',now())->count();
        return ResponseHelper::success($tipCount, 'Tip count fetched successfully', 200);
    }
    public function create(TipRequest $request)
    {
        try {
            $tip = $this->tipService->create($request->validated());
            return ResponseHelper::success($tip, 'Tip created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function getFreeTipofUser()
    {
        try {
            $user = Auth::user();
            $tip = $this->tipService->getFreeTipofUser($user->id);
            return ResponseHelper::success($tip, 'Tip created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function getAllRunningTips()
    {
        try {
            $tips = $this->tipService->getAllRunningTips();
            return ResponseHelper::success($tips, 'Tips fetched successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function getAllVipTips()
    {
        try {
            $tips = $this->tipService->getAllVipTips();
            return ResponseHelper::success($tips, 'Tips fetched successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function approveTip($tipId)
    {
        try {
            $tip = $this->tipService->approveTip($tipId);
            return ResponseHelper::success($tip, 'Tip approved successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function setTipResult(Request $request, $tipId)
    {
        try {
            $result = $request->result;
            $tip = $this->tipService->setTipResult($tipId, $result);
            return ResponseHelper::success($tip, 'Tip result set successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function updateTip(Request $request, $tipId)
    {
        try {
            $status = $request->status;
            $result = $request->result;
            $ods = $request->odds;
            $tip_code = $request->tip_code;
            $match_date = $request->match_date;
            $rejection_reason = $request->rejection_reason;
            $tip = $this->tipService->updateTip($tipId, $status, $result, $ods, $rejection_reason, $tip_code, $match_date);
            return ResponseHelper::success($tip, 'Tip updated successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function getAllTips()
    {
        try {
            $tips = $this->tipService->getAllTips();
            return ResponseHelper::success($tips, 'Tips fetched successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function delete($tipId)
    {
        try {
            $this->tipService->delete($tipId);
            return ResponseHelper::success(null, 'Tip deleted successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
