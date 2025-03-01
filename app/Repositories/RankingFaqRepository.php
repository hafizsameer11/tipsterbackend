<?php

namespace App\Repositories;

use App\Models\RankingFaq;
use Exception;

class RankingFaqRepository
{
    public function all()
    {
        return RankingFaq::all();
    }

    public function find($id)
    {
        // Add logic to find data by ID
    }

    public function create(array $data)
    {
        return RankingFaq::create($data);
    }

    public function update($id, array $data)
    {
        $rankingFaq = RankingFaq::find($id);
        if (!$rankingFaq) {
            throw new Exception('RankingFaq not found');
        }
        return $rankingFaq->update($data);
    }

    public function delete($id)
    {
        $ranking = RankingFaq::find($id);
        if (!$ranking) {
            throw new Exception('RankingFaq not found');
        }
        return $ranking->delete();
    }
}
