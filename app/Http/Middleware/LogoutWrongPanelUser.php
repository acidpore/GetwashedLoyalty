<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A customer still signed in from the loyalty site would hit a bare 403 on the kasir panel,
 * login page included. Sign them out so the panel's own login form shows instead.
 */
class LogoutWrongPanelUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();
        $user = $panel?->auth()->user();

        if ($user && ! $user->canAccessPanel($panel)) {
            $panel->auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect($panel->getLoginUrl());
        }

        return $next($request);
    }
}
