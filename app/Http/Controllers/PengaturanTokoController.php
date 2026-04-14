<?php

namespace App\Http\Controllers;

use App\Models\PengaturanToko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class PengaturanTokoController extends Controller
{
    #[OA\Get(
        path: '/pengaturan-toko',
        tags: ['Pengaturan Toko'],
        summary: 'Lihat pengaturan toko',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Pengaturan toko berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function show()
    {
        $tokoId = Auth::user()->toko_id;
        $pengaturan = PengaturanToko::where('toko_id', $tokoId)->first();
        return response()->json($pengaturan);
    }

    #[OA\Put(
        path: '/pengaturan-toko',
        tags: ['Pengaturan Toko'],
        summary: 'Ubah pengaturan toko',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ppn', type: 'integer', example: 11),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Terima kasih sudah berbelanja'),
                    new OA\Property(property: 'nama_toko', type: 'string', example: 'Toko Maju Jaya')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pengaturan toko berhasil diubah'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function update(Request $request)
    {
        $tokoId = Auth::user()->toko_id;
        $pengaturan = PengaturanToko::where('toko_id', $tokoId)->firstOrFail();
        $this->authorizeUpdate();
        $data = $request->validate([
            'ppn' => 'sometimes|required|integer|min:0|max:100',
            'catatan' => 'nullable|string',
            'nama_toko' => 'sometimes|required|string|max:100',
        ]);
        if (isset($data['nama_toko'])) {
            $pengaturan->toko->update(['nama' => $data['nama_toko']]);
        }
        $pengaturan->update($data);
        return response()->json($pengaturan);
    }

    protected function authorizeUpdate()
    {
        // Hanya admin yang boleh update pengaturan toko
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }
}
