<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $account = $request->attributes->get('account');

        $profile = $account->accountProfile;

        return ApiResponse::success(
            message: 'Maklumat profil berjaya diperoleh.',
            data: [
                'account_id' => $account->id,
                'display_name' => $profile?->display_name,
                'display_name_changed_at' => $profile?->display_name_changed_at?->toISOString(),
                'profile_photo' => $profile?->profile_photo,
            ]
        );
    }
}
