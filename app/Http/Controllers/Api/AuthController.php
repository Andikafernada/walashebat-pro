<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Tukar kredensial dengan Bearer token (untuk CBT/ExamBrowser server-to-server). */
    public function issueToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Kredensial tidak valid.']);
        }

        // Ability dibatasi agar token integrasi tidak bisa mengubah data sensitif.
        $token = $user->createToken($request->device_name, ['read', 'attendance:verify']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'school_name']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->only(['id', 'name', 'email', 'school_name']));
    }

    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['revoked' => true]);
    }
}
