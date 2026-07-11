<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $admin = auth()->guard('admin')->user();

        if (! $admin || ! $admin->can($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
