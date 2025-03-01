<?php

namespace App\Services;

use App\Repositories\BettingCompanyRepository;
use Exception;

class BettingCompanyService
{
    protected $BettingCompanyRepository;

    public function __construct(BettingCompanyRepository $BettingCompanyRepository)
    {
        $this->BettingCompanyRepository = $BettingCompanyRepository;
    }

    public function all()
    {
        try {
            return $this->BettingCompanyRepository->all();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function find($id)
    {
        try {
            return $this->BettingCompanyRepository->find($id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create(array $data)
    {
        try {
            return $this->BettingCompanyRepository->create($data);
        } catch (Exception $e) {
            throw new Exception('Betting Company not created ' . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        try {
            return $this->BettingCompanyRepository->update($id, $data);
        } catch (Exception $e) {
            throw new Exception('Betting Company not updated ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            return $this->BettingCompanyRepository->delete($id);
        } catch (Exception $e) {
            throw new Exception('Betting Company not deleted ' . $e->getMessage());
        }
    }
}
