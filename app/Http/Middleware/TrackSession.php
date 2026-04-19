<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user_id')) {
            session()->put('user_id', session()->getId());
        }
        
        return $next($request);
    }
}