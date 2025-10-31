<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        // Ensure user is admin
        if (!Auth::user()->is_admin) {
            return redirect('/')->with('error', 'Access denied. Admins only.');
        }

        return $next($request);
    }
}
