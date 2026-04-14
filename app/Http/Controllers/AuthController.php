<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    //fungsi untuk login
    #[OA\Post(
        path: '/auth/login',
        tags: ['Auth'],
        summary: 'Login pengguna',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password'],
                properties: [
                    new OA\Property(property: 'login', type: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil.'),
                        new OA\Property(property: 'token', type: 'string', example: '1|token-example'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['login'])
            ->orWhere('username', $credentials['login'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Username/email atau password tidak sesuai.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Akun ini sedang nonaktif.'],
            ]);
        }

        $token = $user->createToken('auth-token', [$user->role])->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $user->load('toko'),
        ]);
    }

    //panggil identitas objek
    #[OA\Get(
        path: '/auth/me',
        tags: ['Auth'],
        summary: 'Ambil data pengguna yang sedang login',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data pengguna berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('toko'),
        ]);
    }


    //fungsi untuk logout
    #[OA\Post(
        path: '/auth/logout',
        tags: ['Auth'],
        summary: 'Logout pengguna',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil.')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        // $token = $request->user()?->currentAccessToken();
        $request->user()->tokens()->delete();

        // if ($token instanceof PersonalAccessToken) {
        //     $token->delete();
        // }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
