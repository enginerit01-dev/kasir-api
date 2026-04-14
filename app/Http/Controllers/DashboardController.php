<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/dashboard',
        tags: ['Dashboard'],
        summary: 'Ringkasan dashboard toko',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan dashboard',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_omzet', type: 'integer', example: 500000),
                        new OA\Property(property: 'total_transaksi', type: 'integer', example: 120),
                        new OA\Property(property: 'omzet_harian', type: 'integer', example: 75000),
                        new OA\Property(property: 'transaksi_harian', type: 'integer', example: 12),
                        new OA\Property(property: 'omzet_bulanan', type: 'integer', example: 2500000),
                        new OA\Property(property: 'transaksi_bulanan', type: 'integer', example: 80),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function summary(Request $request)
    {
        $tokoId = Auth::user()->toko_id;
        $today = now()->toDateString();
        $month = now()->format('Y-m');

        $totalOmzet = Transaksi::where('toko_id', $tokoId)->sum('grand_total');
        $totalTransaksi = Transaksi::where('toko_id', $tokoId)->count();

        $omzetHarian = Transaksi::where('toko_id', $tokoId)
            ->whereDate('tanggal', $today)
            ->sum('grand_total');
        $transaksiHarian = Transaksi::where('toko_id', $tokoId)
            ->whereDate('tanggal', $today)
            ->count();

        $omzetBulanan = Transaksi::where('toko_id', $tokoId)
            ->where('tanggal', 'like', $month.'%')
            ->sum('grand_total');
        $transaksiBulanan = Transaksi::where('toko_id', $tokoId)
            ->where('tanggal', 'like', $month.'%')
            ->count();

        return response()->json([
            'total_omzet' => $totalOmzet,
            'total_transaksi' => $totalTransaksi,
            'omzet_harian' => $omzetHarian,
            'transaksi_harian' => $transaksiHarian,
            'omzet_bulanan' => $omzetBulanan,
            'transaksi_bulanan' => $transaksiBulanan,
        ]);
    }
}
