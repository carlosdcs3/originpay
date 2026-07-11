<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTransactionPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // If not logged in, or already has password, let it pass
        if (!$user || $user->transactionPassword()->exists()) {
            return $next($request);
        }

        // Allow routes to store the password or logout
        if ($request->routeIs('user.transaction-password.store') || $request->routeIs('logout')) {
            return $next($request);
        }

        // If it's an AJAX request, return 403
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => 'É necessário criar uma senha transacional.'], 403);
        }

        // If it's a normal page, the layout will show the global modal.
        // But to be completely secure, we could also redirect them to a specific setup page if we had one.
        // Since we are using a global modal, we let the request pass, but we might flash a session variable 
        // to force the modal to be visible and un-closable.
        // Wait, if we let it pass, they could use the page if they delete the modal node.
        // Actually, returning a redirect back to dashboard if they try to access sensitive pages like 'transfer' would be better.
        // Or we can just let it render the current page (with the unclosable modal blocking it).
        // Let's just let it pass for GET requests so the modal renders on whatever page they are, 
        // but block all POST/PUT/DELETE requests (except the store route).
        
        if (!$request->isMethod('get')) {
            return redirect()->back()->with('error', 'Por favor, crie sua senha transacional antes de realizar operações.');
        }

        return $next($request);
    }
}
