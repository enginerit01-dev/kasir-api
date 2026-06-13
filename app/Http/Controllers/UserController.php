<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     // Hanya role yang berwenang (owner/admin/super_admin) sesuai routes
    //     $this->middleware(function ($request, $next) {
    //         $adminOnly = ['index', 'store', 'update', 'destroy', 'show'];
    //         if (in_array($request->route()->getActionMethod(), $adminOnly)) {
    //             if (!in_array(Auth::user()?->role, ['super_admin', 'owner', 'admin'])) {
    //                 return response()->json(['message' => 'Forbidden'], 403);
    //             }
    //         }
    //         return $next($request);
    //     });
    // }

    #[OA\Get(
        path: '/user',
        tags: ['User'],
        summary: 'Daftar user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'q', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar user berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->filled('q')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%')
                  ->orWhere('email', 'like', '%'.$request->q.'%')
                  ->orWhere('username', 'like', '%'.$request->q.'%');
            });
        }

        // Super admin bisa melihat semua, owner hanya bisa melihat staf
        if (Auth::user()->role !== 'super_admin') {
            $query->where('role', '!=', 'owner');
        }

        return response()->json($query->paginate(10));
    }

    #[OA\Post(
        path: '/user',
        tags: ['User'],
        summary: 'Tambah user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'username', 'password', 'role', 'toko_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Kasir Satu'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'kasir@example.com'),
                    new OA\Property(property: 'username', type: 'string', example: 'kasir1'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'role', type: 'string', example: 'kasir'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'toko_id', type: 'string', example: '01kty...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User berhasil dibuat'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function store(Request $request)
    {
        $authUser = Auth::user();

        $allowedRoles = ['admin', 'kasir'];
        if ($authUser->role === 'super_admin') {
            $allowedRoles[] = 'owner';
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'role' => 'required|in:' . implode(',', $allowedRoles),
            'is_active' => 'boolean',
            // Jika role adalah owner, toko_id opsional (owner dibuat dulu baru toko)
            'toko_id' => 'required_if:role,admin,kasir|exists:toko,id',
        ]);

        // Proteksi: Owner hantokoMilikya boleh membuat user untuk toko miliknya
        if ($authUser->role === 'owner') {
            $ownsToko = $authUser->tokoMilik()->where('id', $data['toko_id'])->exists();
            if (!$ownsToko) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke toko ini.'], 403);
            }
        }

        return DB::transaction(function () use ($data, $authUser) {
            $isOwnerRequest = ($data['role'] === 'owner');

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role' => $isOwnerRequest ? 'owner' : 'staff',
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Hubungkan user ke toko dengan role yang dipilih
            if (!$isOwnerRequest) {
                $user->tokoTugas()->attach($data['toko_id'], [
                    'role' => $data['role'],
                    'is_active' => $data['is_active'] ?? true,
                ]);
            }

            return response()->json($user->load('tokoTugas'), 201);
        });
    }

    #[OA\Get(
        path: '/user/{user}',
        tags: ['User'],
        summary: 'Detail user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'user', description: 'user_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail user berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User tidak ditemukan')
        ]
    )]
    public function show($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'owner' && Auth::user()->role !== 'super_admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($user);
    }

    #[OA\Put(
        path: '/user/{user}',
        tags: ['User'],
        summary: 'Ubah user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'user', description: 'user_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Admin Toko'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'username', type: 'string', example: 'admin1'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'role', type: 'string', example: 'admin'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'toko_id', type: 'string', example: '01kty...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User berhasil diubah'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'owner' && Auth::user()->role !== 'super_admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $allowedRoles = ['admin', 'kasir'];
        if (Auth::user()->role === 'super_admin') {
            $allowedRoles[] = 'owner';
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'username' => 'sometimes|required|string|max:50|unique:users,username,'.$user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|required|in:' . implode(',', $allowedRoles),
            'is_active' => 'boolean',
            'toko_id' => 'sometimes|required|exists:toko,id',
        ]);

        return DB::transaction(function () use ($data, $user) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Update data dasar user (kecuali role sistem tetap staff)
            $userData = collect($data)->except(['role', 'toko_id'])->toArray();
            $user->update($userData);

            // Jika role atau toko_id berubah, update tabel pivot
            if (isset($data['role']) || isset($data['toko_id'])) {
                // Ambil toko aktif saat ini
                $tokoAktif = $user->tokoTugas()->wherePivot('is_active', true)->first();
                $tokoId = $data['toko_id'] ?? $tokoAktif?->id;
                $role = $data['role'] ?? $tokoAktif?->pivot?->role;

                if ($tokoId) {
                    $user->tokoTugas()->syncWithPivotValues([$tokoId], [
                        'role' => $role,
                        'is_active' => $data['is_active'] ?? true
                    ]);
                }
            }

            if (isset($data['is_active']) && $data['is_active'] === false) {
                $user->tokens()->delete();
            }

            return response()->json($user->load('tokoTugas'));
        });
    }

    #[OA\Delete(
        path: '/user/{user}',
        tags: ['User'],
        summary: 'Hapus user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'user', description: 'user_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'User berhasil dihapus'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User tidak ditemukan')
        ]
    )]
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'owner' && Auth::user()->role !== 'super_admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User dihapus']);
    }
}
