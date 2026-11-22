<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $org = $user?->organization;

        if (! $org) {
            Auth::logout();
            return redirect()->route('login');
        }
        if ($org->locked_at && ! $user->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            return response()->view('site.locked', [], 403);
        }
        return $next($request);
    }
}
