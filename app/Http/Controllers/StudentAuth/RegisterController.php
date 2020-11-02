<?php

namespace App\Http\Controllers\StudentAuth;

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
     * Store a newly student in storage and return a jwt associating it.
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

        $student = new Student;

        $student->user_name = $request->input('user_name');
        $student->email = $request->input('email');
        $student->password = bcrypt($request->input('password'));
        $student->first_name = '';
        $student->last_name = '';
        $student->role = 1;
        $student->grade = 0;
        $student->letter = '';
        $student->exchange_id = 0;
        $student->first_friend = 0;
        $student->second_friend = 0;
        $student->third_friend = 0;
        $student->first_wish = 0;
        $student->second_wish = 0;
        $student->third_wish = 0;
        $student->project_id = 0;

        $email = SignUpEmail::all()->where('email', $student->email)->first();
        $admins_with_same_emails = Admin::all()->where('email', $student->email)->count();
        $leaders_with_same_emails = Leader::all()->where('email', $student->email)->count();

        if ($admins_with_same_emails <= 0 && $leaders_with_same_emails <= 0 && !$email) {
            try {
                if ($student->save()) {
                    $token = auth('students')->login($student);

                    return response()->json(['token' => $token]);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Scheinbar ist der Benutzername bereits vergeben.', 500);
            }
        } else {
            return response()->json('Diese E-Mail ist bereits vergeben.', 400);
        }
    }
}
