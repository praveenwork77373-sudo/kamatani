<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MasterCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Codes are system-auto only. This endpoint remains for compatibility
 * and returns that manual entry is not used.
 */
class MasterCodeController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'code' => ['nullable', 'string', 'max:100'],
            'exclude_id' => ['nullable'],
            'medical_institution_id' => ['nullable', 'integer'],
        ]);

        $config = MasterCodeGenerator::configFor($data['type']);
        if (! $config) {
            return response()->json(['ok' => false, 'message' => 'Unknown type'], 422);
        }

        return response()->json([
            'ok' => true,
            'empty' => true,
            'available' => true,
            'auto_only' => true,
            'message' => __('pages.code_auto_only_note'),
            'prefix' => $config['prefix'],
        ]);
    }
}
