<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function login(Request $request)
    {
        // Validate credentials
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate user
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on role
            if ($user->role === 'dilg_admin') {
                return redirect()->route('dilg.dashboard')
                    ->with('success', 'Welcome back, DILG Administrator!');
            }

            if ($user->role === 'barangay_staff') {
                return redirect()->route('barangay.dashboard', ['barangay' => $user->assigned_barangay])
                    ->with('success', 'Welcome back, ' . $user->assigned_barangay . ' Staff!');
            }

            // Fallback (should not happen)
            return redirect()->route('dilg.dashboard');
        }

        // Authentication failed
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
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
}
