<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TokoController extends Controller
{
    #[OA\Get(
        path: '/toko',
        tags: ['Toko'],
        summary: 'Daftar toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'q', description: 'Cari nama atau alamat toko', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar toko berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Toko::query()->with('owner');

        if($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($builder) use ($q) {
                $builder->where('nama', 'like', "%$q%")
                ->orWhere('alamat', 'like', "%$q%");
            });
        }
        return response()->json($query->paginate(10));
    }

    #[OA\Post(
        path: '/toko',
        tags: ['Toko'],
        summary: 'Tambah toko baru',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'alamat', 'telepon', 'owner_id'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Toko Baru'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Merdeka No. 10'),
                    new OA\Property(property: 'telepon', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'owner_id', type: 'string', example: '01kty...'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Toko berhasil dibuat'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'alamat' => 'required|string|max:255',
            'telepon' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'is_active' => 'boolean',
        ]);
        $toko = Toko::create($data);
        return response()->json([
            'message' => 'Toko berhasil dibuat.',
            'data'    => $toko->load('owner'),
        ], 201);
    }

    #[OA\Get(
        path: '/toko/{toko}',
        tags: ['Toko'],
        summary: 'Detail toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', description: 'toko_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail toko berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Toko tidak ditemukan')
        ]
    )]
    public function show(Toko $toko): JsonResponse
    {
        return response()->json($toko->load('owner'));
    }

    #[OA\Put(
        path: '/toko/{toko}',
        tags: ['Toko'],
        summary: 'Ubah data toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', description: 'toko_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Toko Updated'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Baru No. 15'),
                    new OA\Property(property: 'owner_id', type: 'string', example: '01kty...'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Toko berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Toko tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function update(Request $request, Toko $toko): JsonResponse
    {
        $data = $request->validate([
            'nama' => 'sometimes|required|string|max:100',
            'alamat' => 'sometimes|required|string|max:255',
            'owner_id' => 'sometimes|required|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $toko->update($data);
        return response()->json([
            'message' => 'Toko berhasil diperbarui.',
            'data'    => $toko->load('owner'),
        ]);
    }

    #[OA\Delete(
        path: '/toko/{toko}',
        tags: ['Toko'],
        summary: 'Hapus toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'toko', description: 'toko_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Toko berhasil dihapus'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Toko tidak ditemukan')
        ]
    )]
    public function destroy(Toko $toko): JsonResponse
    {
        $toko->delete();
        return response()->json([
            'message' => 'Toko berhasil dihapus.',
        ]);
    }
}
