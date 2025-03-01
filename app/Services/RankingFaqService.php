<?php

namespace App\Services;

use App\Repositories\RankingFaqRepository;
use Exception;

class RankingFaqService
{
    protected $RankingFaqRepository;

    public function __construct(RankingFaqRepository $RankingFaqRepository)
    {
        $this->RankingFaqRepository = $RankingFaqRepository;
    }

    public function all()
    {
        try {
            return $this->RankingFaqRepository->all();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function find($id)
    {
        return $this->RankingFaqRepository->find($id);
    }

    public function create(array $data)
    {
        try {
            return $this->RankingFaqRepository->create($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        try{
            return $this->RankingFaqRepository->update($id, $data);
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            return $this->RankingFaqRepository->delete($id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
