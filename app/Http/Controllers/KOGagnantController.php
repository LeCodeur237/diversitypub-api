<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimKOGagnantPrizeRequest;
use App\Http\Requests\CheckKOGagnantRequest;
use App\Http\Requests\SubmitKOGagnantRequest;
use App\Services\KOGagnantService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Prize',
    type: 'object',
    required: ['label', 'type', 'icon'],
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Depot Wave de 100 Fcfa'),
        new OA\Property(property: 'type', type: 'string', example: 'wave'),
        new OA\Property(property: 'icon', type: 'string', example: 'bi-wallet2'),
    ]
)]
#[OA\Schema(
    schema: 'SubmitKOGagnantResponse',
    type: 'object',
    required: ['ok', 'alreadyPlayed', 'participationId', 'won', 'message'],
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'alreadyPlayed', type: 'boolean', example: false),
        new OA\Property(property: 'participationId', type: 'integer', example: 123),
        new OA\Property(property: 'won', type: 'boolean', example: true),
        new OA\Property(property: 'prize', ref: '#/components/schemas/Prize', nullable: true),
        new OA\Property(property: 'message', type: 'string', example: 'Participation gagnante.'),
    ]
)]
#[OA\Schema(
    schema: 'ClaimKOGagnantResponse',
    type: 'object',
    required: ['ok'],
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'CheckKOGagnantResponse',
    type: 'object',
    required: ['ok', 'alreadyPlayed', 'message'],
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'alreadyPlayed', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Participation autorisee.'),
    ]
)]
class KOGagnantController extends Controller
{
    public function __construct(protected KOGagnantService $service)
    {
    }

    #[OA\Post(
        path: '/api/ko-gagnant/submit',
        tags: ['K.O. Gagnant'],
        summary: 'Enregistrer une participation au jeu',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SubmitKOGagnantRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Participation enregistree',
                content: new OA\JsonContent(ref: '#/components/schemas/SubmitKOGagnantResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Erreur de validation ou participation invalide',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Le numero de telephone ne correspond pas au reseau choisi.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function submit(SubmitKOGagnantRequest $request): JsonResponse
    {
        $result = $this->service->submit($request->validated());

        return response()->json($result);
    }

    #[OA\Post(
        path: '/api/ko-gagnant/check',
        tags: ['K.O. Gagnant'],
        summary: 'Verifier si un numero a deja participe',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CheckKOGagnantRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification effectuee',
                content: new OA\JsonContent(ref: '#/components/schemas/CheckKOGagnantResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Erreur de validation',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Le numero de telephone ne correspond pas au reseau choisi.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function check(CheckKOGagnantRequest $request): JsonResponse
    {
        return response()->json($this->service->checkParticipation($request->validated()));
    }

    #[OA\Post(
        path: '/api/ko-gagnant/claim',
        tags: ['K.O. Gagnant'],
        summary: 'Reclamer un lot Wave',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ClaimKOGagnantRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reclamation reussie',
                content: new OA\JsonContent(ref: '#/components/schemas/ClaimKOGagnantResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Participation introuvable',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [App\\Models\\KOGagnantParticipation] 123'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Participation non eligible ou lot deja reclame',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cette participation n\'est pas gagnante.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function claim(ClaimKOGagnantPrizeRequest $request): JsonResponse
    {
        $this->service->claimPrize($request->validated());

        return response()->json(['ok' => true]);
    }
}
