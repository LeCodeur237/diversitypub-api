<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShootPenaltyRequest;
use App\Http\Requests\StartPenaltyGameRequest;
use App\Models\PenaltyParticipation;
use App\Services\PenaltyGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenaltyGameController extends Controller
{
    public function __construct(private readonly PenaltyGameService $service)
    {
    }

    public function start(StartPenaltyGameRequest $request): JsonResponse
    {
        return response()->json($this->service->start($request->validated()));
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'order' => ['sometimes', 'in:asc,desc'],
        ]);

        $order = $validated['order'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 20;

        $participations = PenaltyParticipation::query()
            ->orderBy('created_at', $order)
            ->orderBy('id', $order)
            ->paginate($perPage)
            ->through(fn (PenaltyParticipation $participation) => [
                'id' => $participation->id,
                'game' => 'penalty',
                'first_name' => $participation->first_name,
                'last_name' => $participation->last_name,
                'phone_number' => $participation->phone_number,
                'team' => $participation->team,
                'attempts' => $participation->attempts,
                'goals' => $participation->goals,
                'completed' => $participation->completed,
                'won' => $participation->goals >= 2,
                'prize_label' => $participation->prize_label,
                'created_at' => $participation->created_at?->toISOString(),
            ]);

        return response()->json($participations);
    }

    public function shoot(ShootPenaltyRequest $request): JsonResponse
    {
        return response()->json($this->service->shoot($request->validated()));
    }
}
