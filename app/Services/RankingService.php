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

    public function getTop30Rankings()
    {
        try {
            return $this->RankingRepository->getTop30Rankings();
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
