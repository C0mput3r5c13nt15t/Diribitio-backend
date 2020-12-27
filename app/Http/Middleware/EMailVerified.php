<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EMailVeried
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $user_type='students')
    {
        if (($request->user($user_type) instanceof MustVerifyEmail && ! $request->user($user_type)->hasVerifiedEmail())) {
            return response()->json('Ihre E-Mail wurde noch nicht verifiziert!', 403);
        }

        return $next($request);
    }
}
