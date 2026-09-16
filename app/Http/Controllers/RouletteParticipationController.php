<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRouletteParticipationRequest;
use App\Services\RouletteParticipationService;
use Illuminate\Http\JsonResponse;

class RouletteParticipationController extends Controller
{
    public function __construct(protected RouletteParticipationService $service)
    {
    }

    public function submit(SubmitRouletteParticipationRequest $request): JsonResponse
    {
        return response()->json($this->service->submit($request->validated()));
    }
}
