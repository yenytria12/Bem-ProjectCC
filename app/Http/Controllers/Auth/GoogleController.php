<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback from Google
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists with this google_id
            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                // Check if user exists with same email
                $user = User::where('email', $googleUser->email)->first();

                if ($user) {
                    // Link existing account with Google
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                    ]);
                } else {
                    // Create new user without role - admin will assign later
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'password' => bcrypt(Str::random(24)),
                    ]);

                    // No role assigned - admin will assign manually
                    Log::info('New Google user registered: ' . $user->email . ' (ID: ' . $user->id . ')');
                }
            } else {
                // Update avatar if changed
                $user->update([
                    'avatar' => $googleUser->avatar,
                ]);
            }

            // Login the user
            Auth::login($user, true);

            Log::info('Google login successful for: ' . $user->email);

            return redirect('/admin');

        } catch (\Exception $e) {
            Log::error('Google login failed: ' . $e->getMessage());
            return redirect('/admin/login')->withErrors(['email' => 'Gagal login dengan Google: ' . $e->getMessage()]);
        }
    }
}
