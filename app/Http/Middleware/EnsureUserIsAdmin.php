<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'Admin access required.');
            }

            return redirect()->route('admin.login')
                ->with('error', 'Please sign in with an admin account.');
        }

        return $next($request);
    }
}
