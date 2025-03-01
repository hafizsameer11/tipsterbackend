<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RankingController extends Controller
{
    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }
    public function getTop30Rankings()
    {
        try {
            $rankings = $this->rankingService->getTop30Rankings();
            return ResponseHelper::success($rankings, 'Rankings fetched successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
        // return $this->rankingService->getTop30Rankings();
    }

    public function getUserRanking()
    {
        try {
            $user = Auth::user();
            $ranking = $this->rankingService->getUserRanking($user->id);
            return ResponseHelper::success($ranking, 'Ranking fetched successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
