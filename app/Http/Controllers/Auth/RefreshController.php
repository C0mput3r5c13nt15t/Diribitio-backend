<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\RequestGuard;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshController extends Controller
{
    /**
     * Refresh a jwt by giving back a new one and blacklisting the old one.
     *
     * @return \Illuminate\Http\Response
     */
    public function refresh()
    {
        try {
            $auth = JWTAuth::parseToken()->getPayload()->get('type');
            $user = auth(JWTAuth::parseToken()->getPayload()->get('type'))->user();
            $newToken = auth()->refresh();
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            return response()->json('Das verwendete Token befindet sich auf der Blacklist.', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json('Das verwendete Token ist nicht mehr gültig.', 401);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json('Dem Token ist kein Benutzer zugeordnet.', 400);
        } catch (\Tymon\JWTAuth\Exceptions\PayloadException $e) {
            return response()->json('Es gab ein Problem mit der Fracht des Tokens', 400);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json('Das verwendete Token ist ungültig.', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json('Es gab eine Fehler mit dem dem Token.', 400);
        }

        return response()->json(['token' => $newToken, 'auth' => $auth, 'user_name' => $user->user_name]);
    }
}
