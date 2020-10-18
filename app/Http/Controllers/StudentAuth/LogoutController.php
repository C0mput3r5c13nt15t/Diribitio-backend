<?php

namespace App\Http\Controllers\StudentAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Log out and blacklist the jwt.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        auth()->guard('students')->logout();

        return response()->json(['message' => 'Erfolgreich ausgeloggt.'], 200);
    }
}
