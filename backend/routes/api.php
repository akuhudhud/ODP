<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::get('/authenticated-test', function (Request $request): JsonResponse {
        $account = $request->attributes->get('account');
        $session = $request->attributes->get('app_session');

        return response()->json([
            'status' => 'success',
            'message' => 'Authenticated.',
            'data' => [
                'account_id' => $account->id,
                'session_id' => $session->id,
            ],
        ]);
    })->middleware('api.session');
});
