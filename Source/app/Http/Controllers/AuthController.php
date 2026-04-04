<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MasterLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $masterCode = $request->query('master_code');

        if (empty($masterCode)) {
            return redirect()->route('auth.error', ['type' => 'unauthorized']);
        }

        $link = MasterLink::active()
            ->where('link_code', $masterCode)
            ->first();

        if (!$link) {
            return redirect()->route('auth.error', ['type' => 'unauthorized']);
        }

        $link->update(['last_used_at' => now()]);

        $request->session()->put('master_code', $masterCode);

        if (!$request->session()->has('username')) {
            return redirect()->route('auth.username.form');
        }

        return redirect()->route('tickets.index');
    }

    public function usernameForm(): View
    {
        return view('auth.username');
    }

    public function setUsername(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z0-9äöüÄÖÜß\s]+$/u'],
        ]);

        $request->session()->put('username', $validated['username']);

        return redirect()->route('tickets.index');
    }

    public function error(Request $request): View
    {
        $type = $request->query('type', 'unknown');

        $messages = [
            'unauthorized' => 'Zugriff verweigert. Bitte verwenden Sie einen gültigen Zugangslink.',
            'invalid_partner' => 'Der Partner-Link ist ungültig oder abgelaufen.',
            'unknown' => 'Ein unbekannter Fehler ist aufgetreten.',
        ];

        $message = $messages[$type] ?? $messages['unknown'];

        return view('auth.error', compact('type', 'message'));
    }

    public function logout(Request $request): View
    {
        $request->session()->flush();

        return view('auth.logout');
    }
}
