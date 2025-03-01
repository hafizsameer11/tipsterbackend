<?php

namespace App\Services;

use App\Repositories\BettingCompanyRepository;

class BettingCompanyService
{
    protected $BettingCompanyRepository;

    public function __construct(BettingCompanyRepository $BettingCompanyRepository)
    {
        $this->BettingCompanyRepository = $BettingCompanyRepository;
    }

    public function all()
    {
        return $this->BettingCompanyRepository->all();
    }

    public function find($id)
    {
        return $this->BettingCompanyRepository->find($id);
    }

    public function create(array $data)
    {
        return $this->BettingCompanyRepository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->BettingCompanyRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->BettingCompanyRepository->delete($id);
    }
}