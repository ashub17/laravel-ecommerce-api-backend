<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create($validated);
        $user->assignRole('customer');

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->respondWithToken($user, $token, 'Registered successfully.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->respondWithToken($user, $token, 'Logged in successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'User fetched successfully.',
            'data' => (new UserResource($request->user()->load('roles')))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
            'data' => null,
        ]);
    }

    /**
     * Auth responses carry the token alongside the user, so they extend the
     * standard envelope with a sibling `token` key rather than burying it in
     * `data`.
     */
    protected function respondWithToken(User $user, string $token, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => (new UserResource($user->load('roles')))->resolve(),
            'token' => $token,
        ], $status);
    }
}
