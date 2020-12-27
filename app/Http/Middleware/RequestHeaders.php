<?php

namespace App\Http\Middleware;

use Closure;

class RequestHeaders
{
    /**
     * Handle an incoming request and check the request headers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      return $next($request)
        ->header('Access-Control-Allow-Origin', 'http://192.248.186.231')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
