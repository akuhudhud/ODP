<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $account = $request->attributes->get('account');

        return ApiResponse::success(
            message: 'Maklumat akaun berjaya diperoleh.',
            data: [
                'account_id' => $account->id,
                'status' => $account->status,
            ]
        );
    }
}
