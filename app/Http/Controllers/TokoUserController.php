<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\TokoUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class TokoUserController extends Controller
{
    /**
     * Pastikan hanya super_admin atau owner yang bisa mengakses controller ini.
     * Owner hanya bisa mengelola staf di toko miliknya sendiri.
     */
    private function authorizeAkses(Toko $toko): ?JsonResponse
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            return null; // boleh akses semua toko
        }

        if ($user->role === 'owner') {
            // Owner hanya boleh kelola toko miliknya
            if ($toko->owner_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke toko ini.',
                ], 403);
            }
            return null;
        }

        return response()->json([
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ], 403);
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX - Lihat semua staf di toko
    // ─────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/toko/{toko}/staf',
        tags: ['Staf Toko'],
        summary: 'Daftar staf di toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', required: true, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'q', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'role', required: false, schema: new OA\Schema(type: 'string', enum: ['admin', 'kasir'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar staf berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Toko tidak ditemukan'),
        ]
    )]
    public function index(Request $request, Toko $toko): JsonResponse
    {
        $error = $this->authorizeAkses($toko);
        if ($error) return $error;

        $query = $toko->staf()->withPivot('role', 'is_active', 'id');

        // Filter pencarian nama / username / email
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%$q%")
                        ->orWhere('username', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
            });
        }

        // Filter berdasarkan role staf (admin/kasir)
        if ($request->filled('role')) {
            $query->wherePivot('role', $request->role);
        }

        return response()->json($query->paginate(10));
    }

    // ─────────────────────────────────────────────────────────────
    // STORE - Assign staf ke toko
    // ─────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/toko/{toko}/staf',
        tags: ['Staf Toko'],
        summary: 'Assign staf ke toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'role'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'string', example: '01kty...'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'kasir'], example: 'kasir'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Staf berhasil di-assign ke toko'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Toko atau user tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function store(Request $request, Toko $toko): JsonResponse
    {
        $error = $this->authorizeAkses($toko);
        if ($error) return $error;

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:admin,kasir',
        ]);

        $staf = User::findOrFail($data['user_id']);

        // Pastikan user yang di-assign adalah staff (bukan super_admin/owner)
        if ($staf->role !== 'staff') {
            return response()->json([
                'message' => 'User ini bukan staff dan tidak bisa di-assign ke toko.',
            ], 422);
        }

        // Cek apakah sudah terdaftar di toko ini
        $sudahAda = TokoUser::where('toko_id', $toko->id)
                            ->where('user_id', $staf->id)
                            ->exists();

        if ($sudahAda) {
            return response()->json([
                'message' => 'User ini sudah terdaftar sebagai staf di toko ini.',
            ], 422);
        }

        $tokoUser = TokoUser::create([
            'toko_id'   => $toko->id,
            'user_id'   => $staf->id,
            'role'      => $data['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'message'   => 'Staf berhasil di-assign ke toko.',
            'data'      => $tokoUser->load('user'),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE - Update role atau status aktif staf di toko
    // ─────────────────────────────────────────────────────────────

    #[OA\Put(
        path: '/toko/{toko}/staf/{user}',
        tags: ['Staf Toko'],
        summary: 'Update role atau status staf di toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'user', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'kasir'], example: 'admin'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data staf berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Staf tidak ditemukan di toko ini'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function update(Request $request, Toko $toko, User $user): JsonResponse
    {
        $error = $this->authorizeAkses($toko);
        if ($error) return $error;

        $tokoUser = TokoUser::where('toko_id', $toko->id)
                            ->where('user_id', $user->id)
                            ->first();

        if (! $tokoUser) {
            return response()->json([
                'message' => 'Staf ini tidak terdaftar di toko tersebut.',
            ], 404);
        }

        $data = $request->validate([
            'role'      => 'sometimes|required|in:admin,kasir',
            'is_active' => 'sometimes|boolean',
        ]);

        $tokoUser->update($data);

        return response()->json([
            'message' => 'Data staf berhasil diperbarui.',
            'data'    => $tokoUser->load('user'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY - Keluarkan staf dari toko
    // ─────────────────────────────────────────────────────────────

    #[OA\Delete(
        path: '/toko/{toko}/staf/{user}',
        tags: ['Staf Toko'],
        summary: 'Keluarkan staf dari toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'user', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Staf berhasil dikeluarkan dari toko'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Staf tidak ditemukan di toko ini'),
        ]
    )]
    public function destroy(Toko $toko, User $user): JsonResponse
    {
        $error = $this->authorizeAkses($toko);
        if ($error) return $error;

        $tokoUser = TokoUser::where('toko_id', $toko->id)
                            ->where('user_id', $user->id)
                            ->first();

        if (! $tokoUser) {
            return response()->json([
                'message' => 'Staf ini tidak terdaftar di toko tersebut.',
            ], 404);
        }

        $tokoUser->delete();

        return response()->json([
            'message' => 'Staf berhasil dikeluarkan dari toko.',
        ]);
    }
}
