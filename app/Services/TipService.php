<?php

namespace App\Services;

use App\Repositories\TipRepository;
use Exception;

class TipService
{
    protected $TipRepository;

    public function __construct(TipRepository $TipRepository)
    {
        $this->TipRepository = $TipRepository;
    }

    public function all()
    {
        return $this->TipRepository->all();
    }

    public function find($id)
    {
        return $this->TipRepository->find($id);
    }

    public function create(array $data)
    {
        try {
            return $this->TipRepository->create($data);
        } catch (Exception $e) {
            throw new Exception('Tip not created ' . $e->getMessage());
        }
    }
    public function getFreeTipofUser($userId)
    {
        try {
            return $this->TipRepository->getFreeTipofUser($userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getAllRunningTips()
    {
        try {
            return $this->TipRepository->getAllRunningTips();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getAllVipTips()
    {
        try {
            return $this->TipRepository->getAllVipTips();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function approveTip($tipId)
    {
        try {
            return $this->TipRepository->approveTip($tipId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getAllTips()
    {
        try {
            return $this->TipRepository->getAllTips();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function setTipResult($tipId, $result)
    {
        try {
            return $this->TipRepository->setTipResult($tipId, $result);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateTip($tipId, $status, $result, $ods, $rejection_reason, $tip_code)
    {
        try {
            return $this->TipRepository->update($tipId, ['status' => $status, 'result' => $result, 'ods' => $ods, 'rejection_reason' => $rejection_reason, 'tip_code' => $tip_code]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete($id)
    {
        return $this->TipRepository->delete($id);
    }
}
