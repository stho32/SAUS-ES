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
        if (!$request->session()->has('username') && !$request->is('username', 'api/*', 'error*', 'logout')) {
            return redirect()->route('username.form');
        }

        return $next($request);
    }
}
