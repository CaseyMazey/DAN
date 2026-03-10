<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRolle
{
    public function handle(Request $request, Closure $next, string ...$rollen): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!in_array(auth()->user()->rolle, $rollen)) {
            abort(403, 'Kein Zugriff.');
        }

        return $next($request);
    }
}