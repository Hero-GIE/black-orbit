<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('firebase_token')) {
            return redirect('/login')->with('error', 'Please login first');
        }

        return $next($request);
    }
}
