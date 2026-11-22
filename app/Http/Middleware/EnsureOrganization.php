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

        // Kein Zugriff (mehr) auf den gewählten Standort? Auf einen anderen wechseln.
        if ($user && (! $org || (! $user->is_admin && ! $user->organizations()->where('organizations.id', $org->id)->exists()))) {
            $first = $user->organizations()->first();
            if ($first) {
                $user->forceFill(['organization_id' => $first->id])->save();
                $org = $first;
            } elseif (! $user->is_admin) {
                $org = null;
            }
        }

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
