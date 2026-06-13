<?php

namespace App\Http\Controllers;

use App\Models\KategoriProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class KategoriProdukController extends Controller
{
    public function __construct()
    {
        // Hanya admin/owner/super_admin yang boleh create, update, delete kategori
        $this->middleware(function ($request, $next) {
            $adminOnly = ['store', 'update', 'destroy'];
            if (in_array($request->route()->getActionMethod(), $adminOnly, true)) {
                if (! Auth::user()?->hasActiveRole(['super_admin', 'owner', 'admin'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }

            return $next($request);
        });
    }

    #[OA\Get(
        path: '/kategori-produk',
        tags: ['Kategori Produk'],
        summary: 'Daftar kategori produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'q', description: 'Cari nama kategori', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar kategori berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request)
    {
        $query = KategoriProduk::query();

        if ($request->filled('q')) {
            $query->where('kategori', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->paginate(10));
    }

    #[OA\Post(
        path: '/kategori-produk',
        tags: ['Kategori Produk'],
        summary: 'Tambah kategori produk',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kategori'],
                properties: [
                    new OA\Property(property: 'kategori', type: 'string', example: 'Minuman')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kategori berhasil dibuat'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();
        $tokoId = $user->getTokoAktifId();

        $rules = [
            'kategori' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kategori_produk', 'kategori')->where('toko_id', $tokoId),
            ],
        ];

        if ($user->role === 'super_admin') {
            $rules['toko_id'] = 'required|exists:toko,id';
        }

        $data = $request->validate($rules);

        $kategori = KategoriProduk::create($data);

        return response()->json($kategori, 201);
    }

    #[OA\Get(
        path: '/kategori-produk/{kategori_produk}',
        tags: ['Kategori Produk'],
        summary: 'Detail kategori produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'kategori_produk', description: 'kategori_produk_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail kategori berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan')
        ]
    )]
    public function show(string $id)
    {
        $kategori = KategoriProduk::findOrFail($id);

        return response()->json($kategori);
    }

    #[OA\Put(
        path: '/kategori-produk/{kategori_produk}',
        tags: ['Kategori Produk'],
        summary: 'Ubah kategori produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'kategori_produk', description: 'kategori_produk_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'kategori', type: 'string', example: 'Makanan')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Kategori berhasil diubah'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function update(Request $request, string $id)
    {
        $kategori = KategoriProduk::findOrFail($id);
        $user = Auth::user();

        $rules = [
            'kategori' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('kategori_produk', 'kategori')
                    ->where('toko_id', $kategori->toko_id)
                    ->ignore($kategori->id),
            ],
        ];

        if ($user->role === 'super_admin') {
            $rules['toko_id'] = 'sometimes|required|exists:toko,id';
        }

        $data = $request->validate($rules);

        $kategori->update($data);

        return response()->json($kategori);
    }

    #[OA\Delete(
        path: '/kategori-produk/{kategori_produk}',
        tags: ['Kategori Produk'],
        summary: 'Hapus kategori produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'kategori_produk', description: 'kategori_produk_id', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Kategori berhasil dihapus'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan')
        ]
    )]
    public function destroy(string $id)
    {
        $kategori = KategoriProduk::findOrFail($id);
        $kategori->delete();

        return response()->noContent();
    }
}
