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

    public function getTop30Rankings()
    {
        try {
            return $this->RankingRepository->getTop30Rankings();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getTop10Rankings()
    {
        try {
            return $this->RankingRepository->getTop10Rankings();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getUserRanking($userId)
    {
        try {
            return $this->RankingRepository->getUserRanking($userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
