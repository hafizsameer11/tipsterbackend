<?php

namespace App\Repositories;

use App\Models\BankDetail;

class BankDetailRepository
{
    public function all()
    {
        // Add logic to fetch all data
    }

    public function find($id)
    {
        // Add logic to find data by ID
    }

    public function create(array $data)
    {

        return BankDetail::create($data);
    }
public function getByUserId($userId)
{
    return BankDetail::where('user_id', $userId)->first();
}
    public function update($id, array $data)
    {
        // Add logic to update data
    }

    public function delete($id)
    {
        // Add logic to delete data
    }
}
