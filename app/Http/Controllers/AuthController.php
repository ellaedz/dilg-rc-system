<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LoginProtectionService;
use App\Services\SecuritySessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login request with real authentication
     */
    public function login(
        Request $request,
        LoginProtectionService $loginProtection,
        SecuritySessionManager $sessionManager,
    ) {
        $request->merge([
            'email' => $loginProtection->normalizeEmail((string) $request->input('email')),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);
        $email = $credentials['email'];
        $ipAddress = $request->ip();

        if ($loginProtection->isRateLimited($email, $ipAddress)) {
            $loginProtection->consumeDummyPasswordCheck($credentials['password']);

            return $this->failedLoginResponse($email);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user) {
            $loginProtection->clearExpiredLock($user, $request);
            $passwordIsValid = Hash::check($credentials['password'], $user->password);
        } else {
            $loginProtection->consumeDummyPasswordCheck($credentials['password']);
            $passwordIsValid = false;
        }

        $isLocked = $user?->locked_until?->isFuture() ?? false;

        if (! $user || ! $user->is_active || $isLocked || ! $passwordIsValid) {
            $loginProtection->hit($email, $ipAddress);

            if ($user?->is_active && ! $isLocked && ! $passwordIsValid) {
                $loginProtection->recordFailedPassword($user, $request);
            }

            return $this->failedLoginResponse($email);
        }

        $loginProtection->recordSuccessfulLogin($user);
        $loginProtection->clearEmailLimit($email);
        Auth::login($user);
        $request->session()->regenerate();
        $sessionManager->establish($request, $user);

        if ($user->must_change_password) {
            return redirect()->route('password.edit')->with(
                'info',
                'Create a private new password before accessing CIVICLEAR.'
            );
        }

        if ($user->role === 'dilg_admin') {
            return redirect()->route('dilg.dashboard')
                ->with('success', 'Welcome back, DILG Administrator!');
        }

        if ($user->role === 'barangay_staff') {
            return redirect()->route('barangay.dashboard', ['barangay' => $user->assigned_barangay])
                ->with('success', 'Welcome back, '.$user->assigned_barangay.' Staff!');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->failedLoginResponse($email);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }

    private function failedLoginResponse(string $normalizedEmail): RedirectResponse
    {
        return back()->withErrors([
            'email' => LoginProtectionService::GENERIC_ERROR,
        ])->withInput(['email' => $normalizedEmail]);
    }
}
