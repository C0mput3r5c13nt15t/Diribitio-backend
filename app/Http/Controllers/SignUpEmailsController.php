<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\SignUpEmail;
use App\Admin;
use App\Leader;
use App\Http\Resources\SignUpEmail as SignUpEmailResource;

class SignUpEmailsController extends Controller
{
    /**
     * Display a listing of the sign_up_emails.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sign_up_emails = SignUpEmail::all();

        return SignUpEmailResource::collection($sign_up_emails);
    }

    /**
     * Store a newly created sign_up_email in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $admin = $this->authUser();

        $validator = Validator::make($request->all(), [
            'email' => 'required|confirmed|email',
        ]);

        if (!Str::contains($request->input('email'), [config('diribitio.required_email_suffix')])) {
            return response()->json('Die Email ist ungültig!', 406);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $sign_up_email = new SignUpEmail;
        $sign_up_email->email = $request->input('email');
        $sign_up_email->created_by = $admin->id;

        $admins_with_same_emails = Admin::all()->where('email', $sign_up_email->email)->count();
        $leaders_with_same_emails = Leader::all()->where('email', $sign_up_email->email)->count();

        if ($admins_with_same_emails <= 0 && $leaders_with_same_emails <= 0 ) {
            if ($sign_up_email->save()) {
                $data = new SignUpEmailResource($sign_up_email);
                return response()->json(['data' => $data, 'message' => 'Die E-Mail wurde erfolgreich für einen Admin-Account freigegeben.'], 200);
            } else {
                return response()->json('Es gab einen Fehler beim Erlauben der E-Mail-Adresse.', 500);
            }
        } else {
            return response()->json('Es existiert bereits ein Account der diese E-mail benutzt.', 400);
        }
    }

    /**
     * Remove the specified sign_up_email from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sign_up_email = SignUpEmail::findOrFail($id);

        if ($sign_up_email->delete()) {
            return response()->json(['message' => 'Die E-Mail wurde erfolgreich für einen Admin-Account gesperrt.'], 200);
        } else {
            return response()->json('Es gab einen Fehler beim Löschen der Tokens.', 500);
        }

    }
}
