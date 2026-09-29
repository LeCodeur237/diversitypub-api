<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShootPenaltyRequest;
use App\Http\Requests\StartPenaltyGameRequest;
use App\Services\PenaltyGameService;
use Illuminate\Http\JsonResponse;

class PenaltyGameController extends Controller
{
    public function __construct(private readonly PenaltyGameService $service)
    {
    }

    public function start(StartPenaltyGameRequest $request): JsonResponse
    {
        return response()->json($this->service->start($request->validated()));
    }

    public function shoot(ShootPenaltyRequest $request): JsonResponse
    {
        return response()->json($this->service->shoot($request->validated()));
    }
}
