<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeOwnPasswordRequest;
use App\Services\PasswordManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', [
            'isRequired' => (bool) $request->user()->must_change_password,
        ]);
    }

    public function update(
        ChangeOwnPasswordRequest $request,
        PasswordManager $passwordManager,
    ): RedirectResponse {
        $wasRequired = (bool) $request->user()->must_change_password;

        $passwordManager->changeOwnPassword(
            $request->user(),
            $request->validated('password'),
            $request,
        );

        if ($wasRequired) {
            $fallback = $request->user()->role === 'dilg_admin'
                ? route('dilg.dashboard')
                : route('barangay.dashboard', $request->user()->assigned_barangay);

            return redirect()->intended($fallback)->with(
                'success',
                'Your private password is active. You can now use CIVICLEAR.'
            );
        }

        return back()->with('success', 'Your password was changed securely.');
    }
}
