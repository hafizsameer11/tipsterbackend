<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\BettingCompanyRequest;
use App\Services\BettingCompanyService;
use Illuminate\Http\Request;

class BettingCompanyController extends Controller
{
    protected $bettingCompanyService;
    public function __construct(BettingCompanyService $bettingCompanyService)
    {
        $this->bettingCompanyService = $bettingCompanyService;
    }
    public function create(BettingCompanyRequest $request)
    {
        try {
            $response = $this->bettingCompanyService->create($request->all());
            return ResponseHelper::success($response, 'Betting Company created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function update($id, BettingCompanyRequest $request)
    {
        try {
            $response = $this->bettingCompanyService->update($id, $request->all());
            return ResponseHelper::success($response, 'Betting Company updated successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function delete($id)
    {
        try {
            $response = $this->bettingCompanyService->delete($id);
            return ResponseHelper::success($response, 'Betting Company deleted successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function getAll()
    {
        try {
            $response = $this->bettingCompanyService->all();
            return ResponseHelper::success($response, 'Betting Companies fetched successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function getOne($id)
    {
        try {
            $response = $this->bettingCompanyService->find($id);
            return ResponseHelper::success($response, 'Betting Company fetched successfully', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
