<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MasterCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            return response()->json([
                'ok' => true,
                'available' => true,
                'empty' => true,
                'message' => __('pages.code_will_auto_generate'),
            ]);
        }

        $institutionId = $config['institution_scoped']
            ? ($data['medical_institution_id'] ?? $request->user()?->customerUser?->medical_institution_id)
            : null;

        if ($config['institution_scoped'] && ! $institutionId && $data['type'] !== 'institution') {
            // Admin forms pass institution_id explicitly
            $institutionId = $data['medical_institution_id'] ?? null;
        }

        $taken = MasterCodeGenerator::exists(
            $config['table'],
            $config['column'],
            $code,
            $data['exclude_id'] ?? null,
            $institutionId ? (int) $institutionId : null,
        );

        return response()->json([
            'ok' => true,
            'available' => ! $taken,
            'empty' => false,
            'message' => $taken
                ? __('pages.code_already_taken')
                : __('pages.code_available'),
        ]);
    }
}
