<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\RankingFaqRequest;
use App\Models\RankingFaq;
use App\Services\RankingFaqService;
use Exception;
use Illuminate\Http\Request;

class RankingFaqController extends Controller
{
    protected $rankingFaqService;
    public function __construct(RankingFaqService $rankingFaqService)
    {
        $this->rankingFaqService = $rankingFaqService;
    }
    public function create(RankingFaqRequest $request)
    {
        try {
            $ranking = $this->rankingFaqService->create($request->all());
            return ResponseHelper::success($ranking, 'RankingFaq created successfully', 201);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function getAll()
    {
        try {
            $ranking = $this->rankingFaqService->all();
            return ResponseHelper::success($ranking, 'RankingFaq fetched successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function delete($id)
    {
        try {
            $ranking = $this->rankingFaqService->delete($id);
            return ResponseHelper::success($ranking, 'RankingFaq deleted successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
    public function update($id, RankingFaqRequest $request)
    {
        try {
            $ranking = $this->rankingFaqService->update($id, $request->all());
            return ResponseHelper::success($ranking, 'RankingFaq updated successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
