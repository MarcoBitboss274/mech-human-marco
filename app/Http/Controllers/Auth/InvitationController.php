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
        $pivot = $this->findPivotByToken($token);
        if (! $pivot) {
            abort(404);
        }

        $user = User::find($pivot['user_id']);
        if (! $user) {
            abort(404);
        }

        $isNew = (bool) $pivot['is_new'];

        if (! $isNew) {
            DB::table($pivot['table'])
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

        $pivot = $this->findPivotByToken($request->token);
        if (! $pivot) {
            abort(404);
        }

        $user = User::find($pivot['user_id']);
        if (! $user) {
            abort(404);
        }

        $user->update([
            'name' => $request->name,
            'surname' => $request->surname,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        DB::table($pivot['table'])
            ->where('invite_token', $request->token)
            ->update(['accepted_at' => now()]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        if ($pivot['table'] === 'supplier_user') {
            return to_route('workspace.supplier.dashboard');
        }

        return to_route('workspace.dashboard');
    }

    /**
     * Find an invite pivot row across building_user and supplier_user.
     *
     * @return array{table: string, user_id: int, is_new: bool}|null
     */
    private function findPivotByToken(string $token): ?array
    {
        $building = DB::table('building_user')->where('invite_token', $token)->first();
        if ($building) {
            return [
                'table' => 'building_user',
                'user_id' => (int) $building->user_id,
                'is_new' => (bool) $building->is_new,
            ];
        }

        $supplier = DB::table('supplier_user')->where('invite_token', $token)->first();
        if ($supplier) {
            return [
                'table' => 'supplier_user',
                'user_id' => (int) $supplier->user_id,
                'is_new' => (bool) $supplier->is_new,
            ];
        }

        return null;
    }
}
