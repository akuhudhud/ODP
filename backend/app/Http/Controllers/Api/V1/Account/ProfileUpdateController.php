<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileUpdateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $account = $request->attributes->get('account');

        if ($account->status !== 'ACTIVE') {
            return ApiResponse::error(
                message: 'Profil tidak boleh diubah ketika akaun tidak aktif.',
                statusCode: 403
            );
        }

        $validator = Validator::make($request->all(), [
            'display_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[\p{L} ]+$/u',
            ],
            'profile_photo' => [
                'sometimes',
                'nullable',
                'string',
                'max:1024',
            ],
        ], [
            'display_name.regex' => 'Nama paparan hanya boleh mengandungi huruf dan ruang.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                message: 'Maklumat profil tidak sah.',
                data: $validator->errors()->toArray(),
                statusCode: 422
            );
        }

        $validated = $validator->validated();

        $profile = DB::table('account_profiles')
            ->where('account_id', $account->id)
            ->first();

        $now = now();

        if (
            array_key_exists('display_name', $validated) &&
            $validated['display_name'] !== null &&
            $profile?->display_name !== $validated['display_name']
        ) {
            if (
                $profile?->display_name_changed_at !== null &&
                $now->lessThan(
                    \Illuminate\Support\Carbon::parse(
                        $profile->display_name_changed_at
                    )->addDays(30)
                )
            ) {
                return ApiResponse::error(
                    message: 'Nama paparan hanya boleh diubah sekali setiap 30 hari.',
                    statusCode: 422
                );
            }
        }

        if ($profile === null) {
            DB::table('account_profiles')->insert([
                'account_id' => $account->id,
                'display_name' => $validated['display_name'] ?? null,
                'display_name_changed_at' => array_key_exists('display_name', $validated)
                    && $validated['display_name'] !== null
                    ? $now
                    : null,
                'profile_photo' => $validated['profile_photo'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $updates = [
                'updated_at' => $now,
            ];

            if (array_key_exists('display_name', $validated)) {
                $updates['display_name'] = $validated['display_name'];

                if (
                    $validated['display_name'] !== $profile->display_name
                    && $validated['display_name'] !== null
                ) {
                    $updates['display_name_changed_at'] = $now;
                }
            }

            if (array_key_exists('profile_photo', $validated)) {
                $updates['profile_photo'] = $validated['profile_photo'];
            }

            DB::table('account_profiles')
                ->where('account_id', $account->id)
                ->update($updates);
        }

        return ApiResponse::success(
            message: 'Profil berjaya dikemas kini.',
            data: [
                'account_id' => $account->id,
            ]
        );
    }
}
