<?php

namespace App\Http\Controllers\AdminAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\RequestGuard;

class LoginController extends Controller
{
    /**
     * Log in with the given credentials and return if correct a jwt associating the admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $creds = $request->only(['email', 'password']);

        if (!$token = auth()->guard('admins')->attempt($creds)) {
            return response()->json('E-Mail und Passwort stimmen nicht überein!', 401);
        }

        $user = auth('admins')->user();

        return response()->json(['token' => $token, 'user_name' => $user->user_name]);
    }
}
