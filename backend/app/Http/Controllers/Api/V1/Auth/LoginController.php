<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\ApiSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function __construct(
        private readonly ApiSessionService $sessionService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'app' => ['required', 'string', 'in:USER,RUNNER'],
            'device_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                message: 'Maklumat log masuk tidak lengkap.',
                data: $validator->errors()->toArray(),
                statusCode: 422
            );
        }

        $validated = $validator->validated();

        $identifier = $validated['identifier'];
        $app = $validated['app'];
        $deviceId = $validated['device_id'];

        $contact = DB::table('account_contacts')
            ->where('value', $identifier)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $contact) {
            return ApiResponse::error(
                message: 'Maklumat log masuk tidak sah.',
                statusCode: 401
            );
        }

        $account = DB::table('accounts')
            ->where('id', $contact->account_id)
            ->first();

        if (! $account || $account->status !== 'ACTIVE') {
            return ApiResponse::error(
                message: 'Akaun tidak tersedia.',
                statusCode: 401
            );
        }

        $security = DB::table('account_security')
            ->where('account_id', $account->id)
            ->first();

        if ($security?->admin_review_required) {
            return ApiResponse::error(
                message:
