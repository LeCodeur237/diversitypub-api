<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRouletteParticipationRequest;
use App\Models\RouletteParticipation;
use App\Services\RouletteParticipationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouletteParticipationController extends Controller
{
    public function __construct(protected RouletteParticipationService $service)
    {
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

        $participations = RouletteParticipation::query()
            ->orderBy('created_at', $order)
            ->orderBy('id', $order)
            ->paginate($perPage)
            ->through(fn ($participation) => [
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
