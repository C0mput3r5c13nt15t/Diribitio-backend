<?php

namespace App\Http\Controllers\StudentAuth;

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
        $this->middleware('auth:students')->only('resend');
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
        if ($request->user('students')->hasVerifiedEmail()) {
            return response()->json('Sie haben ihre E-Mail bereits verifiziert.', 400);
        }

        $request->user('students')->sendEmailVerificationNotification();

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
        auth('students')->onceUsingId($request->route('id'));

        if ($request->route('id') != $request->user('students')->getKey()) {
            throw new AuthorizationException;
        }

        if ($request->user('students')->hasVerifiedEmail()) {
            return Redirect::to(config('diribitio.frontend_url') . '/E-Mail verifizieren/400');
        }

        if ($request->user('students')->markEmailAsVerified()) {
            event(new Verified($request->user('students')));
        }

        return Redirect::to(config('diribitio.frontend_url') . '/E-Mail verifizieren/200');

    }
}
