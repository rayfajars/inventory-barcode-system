<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPageAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect('login');
        }

        // Admin can access all pages
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Karyawan can only access specific pages
        $allowedPages = [
            'log-stock',
            'stock-masuk',
            'stock-keluar',
            'products.index',
            'products.view'
        ];

        $currentPage = $request->route()->getName();

        if (in_array($currentPage, $allowedPages)) {
            return $next($request);
        }

        return redirect()->back()->with('error', 'Unauthorized access.');
    }
}
