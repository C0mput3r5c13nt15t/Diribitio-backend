<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class AuthenticateJWT extends BaseMiddleware
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
        try {
            if (JWTAuth::parseToken()->getPayload()->get('type') === $user_type) {
                JWTAuth::parseToken()->authenticate();
            } else {
                abort(response()->json('Dieses Token ist nicht für diesen Account-Typ gültig', 403));
            }
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            abort(response()->json('Dem Token ist kein Benutzer zugeordnet', 400));
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            abort(response()->json('Das verwendete Token ist ungültig', 401));
        } catch (\Tymon\JWTAuth\Exceptions\PayloadException $e) {
            abort(response()->json('Es gab ein Problem mit der Fracht des Tokens', 400));
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            abort(response()->json('Das verwendete Token ist nicht mehr gültig', 401));
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            abort(response()->json('Das verwendete Token befindet sich auf der Blacklist', 401));
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            abort(response()->json($e->getMessage(), 400));
        }

        return $next($request);
    }
}
