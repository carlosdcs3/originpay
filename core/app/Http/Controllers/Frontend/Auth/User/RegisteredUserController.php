<?php

namespace App\Http\Controllers\Frontend\Auth\User;

use App\Enums\UserRole;
use App\Events\TransactionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserFeature;
use Cookie;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('frontend.auth.user.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validate request
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms'    => ['accepted'],
        ]);

        // 2. Split the public signup name into profile fields.
        $nameParts = preg_split('/\s+/', trim($validated['name']), 2);
        $firstName = $nameParts[0] ?? $validated['name'];
        $lastName = $nameParts[1] ?? '';

        // 3. Determine location (fallback if session missing)
        $location = session('user_location') ?? getLocation();

        // 4. Create user (always as regular user)
        $user = User::create([
            'name'       => $validated['name'],
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'username'   => $this->generateUsername($validated['email']),
            'email'      => $validated['email'],
            'country'    => $location['name'] ?? null,
            'phone'      => null,
            'role'       => UserRole::USER,
            'password'   => Hash::make($validated['password']),
        ]);

        // 5. Handle referral
        if ($referralCode = Cookie::get('referral_code')) {
            $referrer = User::where('referral_code', $referralCode)->first();

            if ($referrer) {
                $parentReferral = Referral::where('referred_user_id', $referrer->id)->first();

                Referral::create([
                    'user_id'            => $referrer->id,
                    'referred_user_id'   => $user->id,
                    'parent_referral_id' => optional($parentReferral)->id,
                ]);
            }
        }

        // 6. Trigger events and login
        event(new Registered($user));
        event(new TransactionUpdated($user));
        UserFeature::syncWithConfigForUser($user->id);
        Auth::login($user);

        notifyEvs('success', __('Cadastro realizado com sucesso.'));

        return redirect()->route('user.dashboard');
    }

    private function generateUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'user';
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }
}
