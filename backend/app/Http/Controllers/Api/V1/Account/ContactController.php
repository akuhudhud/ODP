<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $account = $request->attributes->get('account');

        $contacts = $account->accountContacts()
            ->where('status', 'ACTIVE')
            ->orderBy('type')
            ->get();

        return ApiResponse::success(
            message: 'Maklumat contact berjaya diperoleh.',
            data: [
                'account_id' => $account->id,
                'contacts' => $contacts->map(
                    static function ($contact): array {
                        return [
                            'id' => $contact->id,
                            'type' => $contact->type,
                            'value' => $contact->value,
                            'is_verified' => $contact->is_verified,
                            'verified_at' => $contact->verified_at?->toISOString(),
                        ];
                    }
                )->values()->all(),
            ]
        );
    }
}
