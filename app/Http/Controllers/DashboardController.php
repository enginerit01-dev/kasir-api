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
        $user = Auth::user();

        // If super_admin and no toko_id is passed, return summaries for all stores.
        if ($user->role === 'super_admin' && !$request->has('toko_id')) {
            $tokos = \App\Models\Toko::all();
            $today = now()->toDateString();
            $month = now()->format('Y-m');

            $data = $tokos->map(function ($toko) use ($today, $month) {
                $tokoId = $toko->id;
                $totalOmzet = Transaksi::withoutGlobalScopes()->where('toko_id', $tokoId)->sum('grand_total');
                $totalTransaksi = Transaksi::withoutGlobalScopes()->where('toko_id', $tokoId)->count();

                $omzetHarian = Transaksi::withoutGlobalScopes()->where('toko_id', $tokoId)
                    ->whereDate('tanggal', $today)
                    ->sum('grand_total');
                $transaksiHarian = Transaksi::withoutGlobalScopes()->where('toko_id', $tokoId)
                    ->whereDate('tanggal', $today)
                    ->count();

                $omzetBulanan = Transaksi::withoutGlobalScopes()->where('toko_id', $tokoId)
                    ->where('tanggal', 'like', $month.'%')
                    ->sum('grand_total');
                $transaksiBulanan = Transaksi::withoutGlobalScopes()->where('toko_id', $tokoId)
                    ->where('tanggal', 'like', $month.'%')
                    ->count();

                return [
                    'toko_id' => $tokoId,
                    'nama_toko' => $toko->nama,
                    'total_omzet' => (int) $totalOmzet,
                    'total_transaksi' => (int) $totalTransaksi,
                    'omzet_harian' => (int) $omzetHarian,
                    'transaksi_harian' => (int) $transaksiHarian,
                    'omzet_bulanan' => (int) $omzetBulanan,
                    'transaksi_bulanan' => (int) $transaksiBulanan,
                ];
            });

            return response()->json($data);
        }

        $tokoId = $user->getTokoAktifId();

        if ($user->role === 'super_admin' && $request->has('toko_id')) {
            $tokoId = $request->toko_id;
        }

        if (!$tokoId) {
            return response()->json(['message' => 'Toko ID diperlukan untuk melihat dashboard.'], 400);
        }

        $toko = \App\Models\Toko::find($tokoId);
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
            'toko_id' => $tokoId,
            'nama_toko' => $toko?->nama,
            'total_omzet' => (int) $totalOmzet,
            'total_transaksi' => (int) $totalTransaksi,
            'omzet_harian' => (int) $omzetHarian,
            'transaksi_harian' => (int) $transaksiHarian,
            'omzet_bulanan' => (int) $omzetBulanan,
            'transaksi_bulanan' => (int) $transaksiBulanan,
        ]);
    }
}
