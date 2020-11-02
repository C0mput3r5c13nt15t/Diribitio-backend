<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Encryption\DecryptException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Get the authenticated User.
     *
     * @return \App\Admin or \App\Leader or \App\Student
     */
    public function authUser()
    {
        try {
            $user = auth(JWTAuth::parseToken()->getPayload()->get('type'))->user();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            abort(response()->json('Dem Token ist kein Benutzer zugeordnet.', 400));
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            abort(response()->json('Das verwendete Token ist ungültig.', 401));
        } catch (\Tymon\JWTAuth\Exceptions\PayloadException $e) {
            abort(response()->json('Es gab ein Problem mit der Fracht des Tokens', 400));
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            abort(response()->json('Das verwendete Token ist nicht mehr gültig.', 401));
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            abort(response()->json('Das verwendete Token befindet sich auf der Blacklist.', 401));
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            abort(response()->json('Es gab einen unbekannten Fehler mit dem dem Token.', 400));
        }

        return $user;
    }
}
