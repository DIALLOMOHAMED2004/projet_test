<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Requests\LoginRequest;
use App\Modules\Administration\Requests\SignupRequest;
use App\Modules\Administration\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signup(SignupRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
