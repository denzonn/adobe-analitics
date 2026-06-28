<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/gmail.readonly'
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent'
            ])
            ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        GmailAccount::updateOrCreate(
            [
                'email' => $googleUser->email
            ],
            [
                'google_id' => $googleUser->id,
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
            ]
        );

        return redirect('/');
    }
}
