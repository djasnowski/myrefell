<?php

namespace App\Http\Controllers;

use App\Services\ActionQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionQueueController extends Controller
{
    public function __construct(
        protected ActionQueueService $actionQueueService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'action_type' => 'required|string|in:cook,craft,smelt,gather,train,agility',
            'action_params' => 'required|array',
            'total' => 'required|integer|min:0',
        ]);

        $result = $this->actionQueueService->startQueue(
            $request->user(),
            $request->input('action_type'),
            $request->input('action_params'),
            $request->input('total')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function cancel(Request $request): JsonResponse
    {
        $result = $this->actionQueueService->cancelQueue($request->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function dismiss(Request $request): JsonResponse
    {
        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $result = $this->actionQueueService->dismissQueue(
            $request->user(),
            $request->input('queue_id')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
