<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRouletteParticipationRequest;
use App\Models\RouletteParticipation;
use App\Services\RouletteParticipationService;
use Illuminate\Http\JsonResponse;

class RouletteParticipationController extends Controller
{
    public function __construct(protected RouletteParticipationService $service)
    {
    }

    public function index(): JsonResponse
    {
        $participations = RouletteParticipation::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($participation) => [
                'id' => $participation->id,
                'game' => $participation->game,
                'first_name' => $participation->first_name,
                'last_name' => $participation->last_name,
                'age' => $participation->age,
                'phone_number' => $participation->phone_number,
                'device_id' => $participation->device_id,
                'won' => (bool) $participation->won,
                'prize_label' => $participation->prize_label,
                'created_at' => $participation->created_at?->toISOString(),
            ]);

        return response()->json($participations);
    }

    public function submit(SubmitRouletteParticipationRequest $request): JsonResponse
    {
        return response()->json($this->service->submit($request->validated()));
    }
}
