<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Cashiers go back to the till login; admins land on the public home page.
        return redirect(filament()->getCurrentPanel()?->getId() === 'kasir' ? filament()->getLoginUrl() : '/');
    }
}
