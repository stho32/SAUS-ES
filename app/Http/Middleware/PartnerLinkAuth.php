<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerLinkAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $partnerLink = $request->query('partner_link') ?? $request->session()->get('partner_link');

        if ($partnerLink) {
            $partner = Partner::where('partner_link', $partnerLink)->first();
            if ($partner) {
                $request->session()->put('partner_link', $partnerLink);
                $request->session()->put('partner_ticket_id', $partner->ticket_id);
                return $next($request);
            }
        }

        return redirect()->route('error', ['type' => 'invalid_partner']);
    }
}
