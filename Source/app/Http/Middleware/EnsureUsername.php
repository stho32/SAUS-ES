<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsername
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('username')) {
            if ($request->is('*/api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Benutzername nicht gesetzt.',
                ], 401);
            }

            return redirect()->route('username.form');
        }

        return $next($request);
    }
}
