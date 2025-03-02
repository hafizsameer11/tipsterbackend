<?php

namespace App\Services;

use App\Repositories\BankDetailRepository;
use Exception;
use Illuminate\Support\Facades\Auth;

class BankDetailService
{
    protected $BankDetailRepository;

    public function __construct(BankDetailRepository $BankDetailRepository)
    {
        $this->BankDetailRepository = $BankDetailRepository;
    }

    public function all()
    {
        return $this->BankDetailRepository->all();
    }

    public function find($id)
    {
        return $this->BankDetailRepository->find($id);
    }

    public function create(array $data)
    {
        try {
            $user = Auth::user();
            $data['user_id'] = $user->id;
            return $this->BankDetailRepository->create($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getByUserId($userId)
    {
        try {
            return $this->BankDetailRepository->getByUserId($userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function update($id, array $data)
    {
        return $this->BankDetailRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->BankDetailRepository->delete($id);
    }
}
