<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance page or redirect existing users to login.
     */
    public function index(string $token): Response|RedirectResponse
    {
        $pivot = DB::table('building_user')->where('invite_token', $token)->first();
        if (! $pivot) {
            abort(404);
        }

        $user = User::find($pivot->user_id);
        if (! $user) {
            abort(404);
        }

        $isNew = (bool) $pivot->is_new;

        if (! $isNew) {
            DB::table('building_user')
                ->where('invite_token', $token)
                ->update(['accepted_at' => now()]);

            return to_route('login');
        }

        return Inertia::render('auth/AcceptInvitation', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    /**
     * Store the user data and log them in.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'name' => 'required|string',
            'surname' => 'required|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privacy' => 'accepted',
        ]);

        $pivot = DB::table('building_user')->where('invite_token', $request->token)->first();
        if (! $pivot) {
            abort(404);
        }

        $user = User::find($pivot->user_id);
        if (! $user) {
            abort(404);
        }

        $user->update([
            'name' => $request->name,
            'surname' => $request->surname,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        DB::table('building_user')
            ->where('invite_token', $request->token)
            ->update(['accepted_at' => now()]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return to_route('workspace.dashboard');
    }
}