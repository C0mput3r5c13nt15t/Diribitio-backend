<?php

namespace App\Http\Controllers\LeaderAuth;

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
     * Store a newly leader in storage and return a jwt associating it.
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
            return response()->json("Die Email ist ungültig!", 406);
        }

        if ($validator->fails()) {
            return response()->json("Die mitgesendeten Daten der Anfrage sind ungültig.", 406);
        }

        $leader = new Leader;

        $leader->user_name = $request->input('user_name');
        $leader->email = $request->input('email');
        $leader->password = bcrypt($request->input('password'));
        $leader->project_id = 0;

        $email = SignUpEmail::all()->where('email', $leader->email)->first();
        $admins_with_same_emails = Admin::all()->where('email', $leader->email)->count();
        $students_with_same_emails = Student::all()->where('email', $leader->email)->count();

        if ($admins_with_same_emails <= 0 && $students_with_same_emails <= 0 && !$email) {
            try {
                if ($leader->save()) {
                    $token = auth('leaders')->login($leader);

                    return response()->json(['token' => $token]);
                } else {
                    return response()->json('Es gab einen Fehler beim Erstellen ihres Accounts.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Es gab einen Fehler beim Erstellen deines Accounts. Scheinbar ist der Benutzername oder die E-Mail bereits vergeben.', 500);
            }
        } else {
            return response()->json('Diese E-Mail ist bereits vergeben.', 400);
        }
    }
}
