<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($roles === []) {
            return $next($request);
        }

        //cek role sistem super admin dan owner
        if (in_array($user->role, $roles, true)){
            return $next($request);
        }

        //cek role staf di toko (admin, kasir)
        //ambil toko aktif user dari toko_user
        $tokoAktif = $user->tokoTugas()->wherePivot('is_active', true)->first();
        $roleStaf = $tokoAktif?->pivot?->role;

        if ($roleStaf && in_array($roleStaf, $roles, true)){
            return $next($request);
        }

        return new JsonResponse([
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ], 403);
    }
}
