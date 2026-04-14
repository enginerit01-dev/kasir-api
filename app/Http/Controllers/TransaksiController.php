<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\PengaturanToko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TransaksiController extends Controller
{
    public function __construct()
    {
        // Hanya kasir/admin yang boleh create transaksi
        $this->middleware(function ($request, $next) {
            $allowed = ['store'];
            if (in_array($request->route()->getActionMethod(), $allowed)) {
                if (!in_array(Auth::user()?->role, ['admin', 'kasir'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }
            return $next($request);
        });
    }

    #[OA\Post(
        path: '/transaksi',
        tags: ['Transaksi'],
        summary: 'Buat transaksi baru',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items', 'metode_pembayaran', 'nominal_bayar'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'produk_id', type: 'integer', example: 1),
                                new OA\Property(property: 'jumlah', type: 'integer', example: 2)
                            ],
                            type: 'object'
                        )
                    ),
                    new OA\Property(property: 'metode_pembayaran', type: 'string', example: 'tunai'),
                    new OA\Property(property: 'nominal_bayar', type: 'integer', example: 50000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Transaksi berhasil dibuat'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();
        $tokoId = $user->toko_id;
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'metode_pembayaran' => 'required|string',
            'nominal_bayar' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($data, $user, $tokoId) {
            $produkList = Produk::whereIn('id', collect($data['items'])->pluck('produk_id'))->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            $details = [];
            foreach ($data['items'] as $item) {
                $produk = $produkList[$item['produk_id']] ?? null;
                if (!$produk || $produk->stok < $item['jumlah']) {
                    abort(400, 'Stok produk tidak cukup: ' . ($produk ? $produk->nama : ''));
                }
                $harga = $produk->harga;
                $sub = $harga * $item['jumlah'];
                $subtotal += $sub;
                $details[] = [
                    'produk_id' => $produk->id,
                    'jumlah' => $item['jumlah'],
                    'harga_saat_transaksi' => $harga,
                    'subtotal' => $sub,
                ];
            }
            $pengaturan = PengaturanToko::where('toko_id', $tokoId)->first();
            $ppn = $pengaturan?->ppn ?? 0;
            $totalPpn = (int) round($subtotal * $ppn / 100);
            $grandTotal = $subtotal + $totalPpn;
            if ($data['nominal_bayar'] < $grandTotal) {
                abort(400, 'Nominal bayar kurang dari total.');
            }
            // Buat transaksi
            $transaksi = Transaksi::create([
                'kode_transaksi' => 'TRX' . now()->format('YmdHis') . rand(100,999),
                'tanggal' => now(),
                'subtotal' => $subtotal,
                'total_ppn' => $totalPpn,
                'grand_total' => $grandTotal,
                'nominal_bayar' => $data['nominal_bayar'],
                'metode_pembayaran' => $data['metode_pembayaran'],
                'kembalian' => $data['nominal_bayar'] - $grandTotal,
                'status' => 1,
                'user_id' => $user->id,
                'toko_id' => $tokoId,
            ]);
            // Simpan detail dan kurangi stok
            foreach ($details as $detail) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    ...$detail
                ]);
                $produk = $produkList[$detail['produk_id']];
                $produk->decrement('stok', $detail['jumlah']);
            }
            return response()->json($transaksi->load('detailTransaksi'), 201);
        });
    }
}
