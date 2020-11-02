<?php

namespace App\Http\Controllers\AdminAuth;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Student;
use App\Admin;
use App\Leader;
use App\SignUpEmail;

class RegisterController extends Controller
{
    /**
     * Store a newly created admin in storage and return a jwt associating it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|min:5',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if (!Str::contains($request->input('email'), [config('diribitio.required_email_suffix')])) {
            return response()->json('Die Email ist ungültig!', 406);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $admin = new Admin;

        $admin->user_name = $request->input('user_name');
        $admin->email = $request->input('email');
        $admin->password = bcrypt($request->input('password'));

        $email = SignUpEmail::all()->where('email', $admin->email)->first();
        $leaders_with_same_emails = Leader::all()->where('email', $admin->email)->count();
        $students_with_same_emails = Student::all()->where('email', $admin->email)->count();

        if ($leaders_with_same_emails <= 0 && $students_with_same_emails <= 0) {
            if ($email) {
                try {
                    if ($admin->save()) {
                        $token = auth('admins')->login($admin);
                        $email->delete();

                        return response()->json(['token' => $token]);
                    } else {
                        return response()->json('Es gab einen unbekannten Fehler.', 500);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    return response()->json('Scheinbar ist der Benutzername oder die E-Mail bereits vergeben.', 500);
                }
            } else {
                return response()->json('Sie sind nicht berechtigt sich als Admin zu registrieren.', 403);
            }
        } else {
            return response()->json('Diese E-Mail ist bereits vergeben.', 400);
        }
    }
}
