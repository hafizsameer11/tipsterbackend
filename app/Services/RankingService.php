<?php

namespace App\Services;

use App\Repositories\RankingRepository;
use Exception;

class RankingService
{
    protected $RankingRepository;

    public function __construct(RankingRepository $RankingRepository)
    {
        $this->RankingRepository = $RankingRepository;
    }
    public function createWinnersAmount($rank, $amount)
    {
        try {
            return $this->RankingRepository->createWinnersAmount($rank, $amount);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getWinnersAmount()
    {
        try {
            return $this->RankingRepository->getWinnersAmount();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getWinnersAmountByRank($rank)
    {
        try {
            return $this->RankingRepository->getWinnersAmountByRank($rank);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function updateWinnersAmounts($winnersData)
    {
        try {
            return $this->RankingRepository->updateWinnersAmounts($winnersData);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getTop30Rankings($weeksAgo = 1)
    {
        try {
            return $this->RankingRepository->getTop30Rankings($weeksAgo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getTop10Rankings($weeksAgo=1)
    {
        try {
            return $this->RankingRepository->getTop10Rankings($weeksAgo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getUserRanking($userId, $weeksAgo)
    {
        try {
            return $this->RankingRepository->getUserRanking($userId, $weeksAgo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function payRankingPayment($userId, $amount, $rank)
    {
        try {
            return $this->RankingRepository->payRankingPayment($userId, $amount, $rank);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
