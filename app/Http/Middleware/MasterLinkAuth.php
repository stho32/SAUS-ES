<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\MasterLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterLinkAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $linkCode = $request->query('master_code') ?? $request->session()->get('master_code');

        if ($linkCode && $this->validateMasterLink($linkCode)) {
            $request->session()->put('master_code', $linkCode);
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return redirect()->route('error', ['type' => 'unauthorized']);
    }

    private function validateMasterLink(string $linkCode): bool
    {
        $masterLink = MasterLink::active()->where('link_code', $linkCode)->first();

        if ($masterLink) {
            $masterLink->update(['last_used_at' => now()]);
            return true;
        }

        return false;
    }
}
