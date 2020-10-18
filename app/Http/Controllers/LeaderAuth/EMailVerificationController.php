<?php

namespace App\Http\Controllers\LeaderAuth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Redirect;

class EMailVerificationController extends Controller
{

    /**
     *  This controller overwrites the standard EMail Verification Controller.
     */

    use VerifiesEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:leaders')->only('resend');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resend(Request $request)
    {
        if ($request->user('leaders')->hasVerifiedEmail()) {
            return response()->json('Sie haben ihre E-Mail bereits verifiziert.', 400);
        }

        $request->user('leaders')->sendEmailVerificationNotification();

        return response()->json(['message' => 'Die verifizierungs E-mail wurde erfolgreich versandt.']);

    }


    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        auth('leaders')->onceUsingId($request->route('id'));

        if ($request->route('id') != $request->user('leaders')->getKey()) {
            throw new AuthorizationException;
        }

        if ($request->user('leaders')->hasVerifiedEmail()) {
            # return response('Sie haben ihre E-Mail bereits verifiziert.', 400);
            return Redirect::to('http://localhost:8100/Projekttage/E-Mail verifizieren/400');
        }

        if ($request->user('leaders')->markEmailAsVerified()) {
            event(new Verified($request->user('leaders')));
        }

        return Redirect::to('http://localhost:8100/Projekttage/E-Mail verifizieren/200');

    }
}
