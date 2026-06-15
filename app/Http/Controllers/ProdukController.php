<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class ProdukController extends Controller
{
    public function __construct()
    {
        // Hanya admin/owner/super_admin yang boleh create, update, delete produk
        $this->middleware(function ($request, $next) {
            $adminOnly = ['store', 'update', 'destroy'];
            if (in_array($request->route()->getActionMethod(), $adminOnly)) {
                if (! Auth::user()?->hasActiveRole(['super_admin', 'owner', 'admin'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }

            return $next($request);
        });
    }

    #[OA\Get(
        path: '/produk',
        tags: ['Produk'],
        summary: 'Daftar produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'q', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'kategori_id', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar produk berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Produk::query();

        // Search
        if ($request->filled('q')) {
            $query->where('nama', 'like', '%'.$request->q.'%');
        }

        // Filter by kategori

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        return response()->json($query->paginate(10));
    }

    #[OA\Post(
        path: '/produk',
        tags: ['Produk'],
        summary: 'Tambah produk',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nama', 'kategori_id', 'harga', 'stok', 'kode_produk'],
                    properties: [
                        new OA\Property(
                            property: 'nama',
                            type: 'string',
                            example: 'Teh Botol'
                        ),
                        new OA\Property(
                            property: 'kategori_id',
                            type: 'string',
                            example: '01kty...'
                        ),
                        new OA\Property(
                            property: 'harga',
                            type: 'integer',
                            example: 5000
                        ),
                        new OA\Property(
                            property: 'stok',
                            type: 'integer',
                            example: 20
                        ),
                        new OA\Property(
                            property: 'kode_produk',
                            type: 'string',
                            example: 'PRD-001'
                        ),
                        new OA\Property(
                            property: 'is_active',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'gambar',
                            type: 'string',
                            format: 'binary',
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Produk berhasil dibuat'),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal'
            ),
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();

        // Tentukan toko_id yang akan digunakan untuk validasi unique.
        // Jika super_admin, ambil dari request. Jika bukan, ambil dari toko aktif user.
        $tokoId = ($user->role === 'super_admin')
            ? $request->input('toko_id')
            : $user->getTokoAktifId();

        // Konversi string "true"/"false" dari multipart/form-data menjadi boolean asli
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $rules = [
            'nama' => 'required|string|max:100',
            'kategori_id' => 'required|exists:kategori_produk,id',
            'harga' => 'required|integer',
            'stok' => 'required|integer',
            'kode_produk' => [
                'required', 'string', 'max:50',
                Rule::unique('produk', 'kode_produk')->where('toko_id', $tokoId)
            ],
            'is_active' => 'boolean',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if ($user->role === 'super_admin') {
            $rules['toko_id'] = 'required|exists:toko,id';
        }

        // Validasi akan otomatis melempar Exception jika gagal
        $data = $request->validate($rules);

        // Jika bukan super_admin, pastikan toko_id tetap menggunakan toko aktif user
        if ($user->role !== 'super_admin') {
            $data['toko_id'] = $user->getTokoAktifId();
        }

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('produk', 'public');
            $data['gambar'] = config('app.url').'/storage/'.$path;
        }

        $produk = Produk::create($data);

        return response()->json($produk, 201);
    }

    #[OA\Get(
        path: '/produk/{produk}',
        tags: ['Produk'],
        summary: 'Detail produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'produk', description: 'produk_id', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail produk berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Produk tidak ditemukan'),
        ]
    )]
    public function show($id)
    {
        $produk = Produk::findOrFail($id);

        return response()->json($produk);
    }

    #[OA\Put(
        path: '/produk/{produk}',
        tags: ['Produk'],
        summary: 'Ubah produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'produk', description: 'produk_id', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'nama',
                            type: 'string',
                            example: 'Teh Botol Sosro'
                        ),
                        new OA\Property(
                            property: 'kategori_id',
                            type: 'string',
                            example: '01kty...'
                        ),
                        new OA\Property(
                            property: 'harga',
                            type: 'integer',
                            example: 6000
                        ),
                        new OA\Property(
                            property: 'stok',
                            type: 'integer',
                            example: 25
                        ),
                        new OA\Property(
                            property: 'kode_produk',
                            type: 'string',
                            example: 'PRD-001'
                        ),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'gambar',
                            type: 'string',
                            format: 'binary',
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Produk berhasil diubah'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Produk tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function update(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);
        $user = Auth::user();

        // Konversi string "true"/"false" dari multipart/form-data menjadi boolean asli
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Jika super_admin mengubah toko_id, gunakan yang baru untuk validasi unique
        $tokoId = ($user->role === 'super_admin' && $request->filled('toko_id'))
            ? $request->toko_id
            : $produk->toko_id;

        $rules = [
            'nama' => 'sometimes|required|string|max:100',
            'kategori_id' => 'sometimes|required|exists:kategori_produk,id',
            'harga' => 'sometimes|required|integer',
            'stok' => 'sometimes|required|integer',
            'kode_produk' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('produk', 'kode_produk')->where('toko_id', $tokoId)->ignore($produk->id)
            ],
            'is_active' => 'boolean',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if ($user->role === 'super_admin') {
            $rules['toko_id'] = 'sometimes|required|exists:toko,id';
        }

        $data = $request->validate($rules);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('produk', 'public');
            $data['gambar'] = config('app.url').'/storage/'.$path;
        }

        $produk->update($data);

        return response()->json($produk);
    }

    #[OA\Delete(
        path: '/produk/{produk}',
        tags: ['Produk'],
        summary: 'Hapus produk',
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'produk', description: 'produk_id', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Produk berhasil dihapus'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Produk tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $produk = Produk::findOrFail($id);
        $produk->delete();

        return response()->noContent();
    }
}
