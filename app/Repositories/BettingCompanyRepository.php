<?php

namespace App\Repositories;

use App\Models\BettingCompany;
use Exception;

class BettingCompanyRepository
{
    public function all()
    {
        return BettingCompany::all();
    }

    public function find($id)
    {
        $company = BettingCompany::find($id);
        if (!$company) {
            throw new Exception('Betting company not found');
        }
        return $company;
    }

    public function create(array $data)
    {
        if (isset($data['logo']) && $data['logo']) {
            $path = $data['logo']->store('logo', 'public');
            $data['logo'] = $path;
        }
        return BettingCompany::create($data);
    }

    public function update($id, array $data)
    {
        $company = BettingCompany::find($id);
        if (!$company) {
            throw new Exception('Betting company not found');
        }
        if (isset($data['logo']) && $data['logo']) {
            $path = $data['logo']->store('logo', 'public');
            $data['logo'] = $path;
        }
        $company->update($data);
        return $company;
    }

    public function delete($id)
    {
        $company = BettingCompany::find($id);
        if (!$company) {
            throw new Exception('Betting company not found');
        }
        // $company->delete();
    }
}
