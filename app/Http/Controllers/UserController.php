<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    public function __construct()
    {
        // Hanya admin yang boleh CRUD user
        $this->middleware(function ($request, $next) {
            $adminOnly = ['index', 'store', 'update', 'destroy', 'show'];
            if (in_array($request->route()->getActionMethod(), $adminOnly)) {
                if (Auth::user()?->role !== 'admin') {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }
            return $next($request);
        });
    }

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
        return response()->json($query->where('role', '!=', 'owner')->paginate(10));
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
                    new OA\Property(property: 'toko_id', type: 'integer', example: 1)
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
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,kasir',
            'is_active' => 'boolean',
            'toko_id' => 'required|exists:toko,id',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json($user, 201);
    }

    #[OA\Get(
        path: '/user/{id}',
        tags: ['User'],
        summary: 'Detail user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer'))
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
        if ($user->role === 'owner') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($user);
    }

    #[OA\Put(
        path: '/user/{id}',
        tags: ['User'],
        summary: 'Ubah user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer'))
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
                    new OA\Property(property: 'toko_id', type: 'integer', example: 1)
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
        if ($user->role === 'owner') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'username' => 'sometimes|required|string|max:50|unique:users,username,'.$user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|required|in:admin,kasir',
            'is_active' => 'boolean',
            'toko_id' => 'sometimes|required|exists:toko,id',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json($user);
    }

    #[OA\Delete(
        path: '/user/{id}',
        tags: ['User'],
        summary: 'Hapus user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer'))
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
        if ($user->role === 'owner') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User dihapus']);
    }
}
