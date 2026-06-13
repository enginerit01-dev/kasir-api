<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class LaporanController extends Controller
{
    #[OA\Get(
        path: '/laporan/kasir',
        tags: ['Laporan'],
        summary: 'Laporan transaksi kasir',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'kasir_id', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'tanggal', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Laporan kasir berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function kasir(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasActiveRole(['super_admin', 'owner', 'admin', 'kasir'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // For non-super_admin, make sure they have a store
        if ($user->role !== 'super_admin' && !$user->getTokoAktifId()) {
            return response()->json(['message' => 'Toko ID diperlukan.'], 400);
        }

        $kasirId = $request->input('kasir_id');
        $query = Transaksi::query();
        if ($kasirId) {
            $query->where('user_id', $kasirId);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }
        $data = $query->with('user')->get();
        return response()->json($data);
    }

    #[OA\Get(
        path: '/laporan/keuangan',
        tags: ['Laporan'],
        summary: 'Laporan keuangan toko',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'tanggal', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Laporan keuangan berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function keuangan(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasActiveRole(['super_admin', 'owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // For non-super_admin, make sure they have a store
        if ($user->role !== 'super_admin' && !$user->getTokoAktifId()) {
            return response()->json(['message' => 'Toko ID diperlukan.'], 400);
        }

        $query = Transaksi::query();
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }
        $data = $query->get();
        $total = $data->sum('grand_total');
        return response()->json([
            'total' => $total,
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: '/laporan/produk-terlaris',
        tags: ['Laporan'],
        summary: 'Laporan produk terlaris',
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'tanggal', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Laporan produk terlaris berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function produkTerlaris(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasActiveRole(['super_admin', 'owner', 'admin', 'kasir'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // For non-super_admin, make sure they have a store
        if ($user->role !== 'super_admin' && !$user->getTokoAktifId()) {
            return response()->json(['message' => 'Toko ID diperlukan.'], 400);
        }

        $tanggal = $request->input('tanggal');
        $query = DetailTransaksi::select('produk_id', DB::raw('SUM(jumlah) as total_terjual'));

        if ($tanggal) {
            $query->whereHas('transaksi', function($q) use ($tanggal) {
                $q->whereDate('tanggal', $tanggal);
            });
        }

        $query->groupBy('produk_id')
            ->orderByDesc('total_terjual')
            ->with('produk')
            ->limit(10);

        $data = $query->get();
        return response()->json($data);
    }
}
