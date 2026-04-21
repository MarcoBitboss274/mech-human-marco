<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Admin\RegisteredUserForAdmin;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privacy' => 'accepted',
            'odontoiatra' => 'required|boolean',
            'odontotecnico' => 'required|boolean',
            'roll_number' => 'required_if:odontoiatra,true|nullable|string|max:255',
            'roll_province' => 'required_if:odontoiatra,true|nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'odontoiatra' => $request->odontoiatra,
            'odontotecnico' => $request->odontotecnico,
            'roll_number' => $request->roll_number ?? null,
            'roll_province' => $request->roll_province ?? null,
        ]);

        $user->makeCustomer();

        event(new Registered($user));

        NotificationService::sendToAdmins(new RegisteredUserForAdmin($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
