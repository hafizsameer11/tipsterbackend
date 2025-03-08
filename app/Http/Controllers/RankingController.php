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
    public function createRankingPayment(Request $request)
    {
        try {
            $rank = $request->rank;
            $amount = $request->amount;
            $ranking = $this->rankingService->createWinnersAmount($rank, $amount);
            return ResponseHelper::success($ranking, 'Ranking payment created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(),  500);
        }
    }
    public function updateWinnersAmounts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'winners' => 'required|array|min:10', // Ensure at least 10 rankings
                'winners.*.rank' => 'required|integer|min:1|max:10',
                'winners.*.amount' => 'required|numeric|min:0',
            ]);

            $updatedWinners = $this->rankingService->updateWinnersAmounts($validatedData['winners']);
            return ResponseHelper::success($updatedWinners, 'Winners amounts updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function getWinnersAmount()
    {
        try {
            $amount = $this->rankingService->getWinnersAmount();
            return ResponseHelper::success($amount, 'Winners amount fetched successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function getWinnersAmountByRank($rank)
    {
        try {
            $amount = $this->rankingService->getWinnersAmountByRank($rank);
            return ResponseHelper::success($amount, 'Winners amount fetched successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
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
    public function getTop10Rankings()
    {
        try {
            $rankings = $this->rankingService->getTop10Rankings();
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
