<?php

namespace App\Http\Controllers\LeaderAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Log out and blacklist the jwt token.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        auth()->guard('leaders')->logout();

        return response()->json(['message' => 'Erfolgreich ausgeloggt.'], 200);
    }
}
