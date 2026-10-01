<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminResetPasswordRequest;
use App\Models\User;
use App\Services\PasswordManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AccountManagementController extends Controller
{
    public function index(): View
    {
        $accounts = User::query()
            ->where('role', 'barangay_staff')
            ->orderBy('assigned_barangay')
            ->orderBy('email')
            ->get();

        return view('account-management.index', compact('accounts'));
    }

    public function resetPassword(
        AdminResetPasswordRequest $request,
        User $account,
        PasswordManager $passwordManager,
    ): RedirectResponse {
        $passwordManager->resetByAdministrator(
            $request->user(),
            $account,
            $request->validated('password'),
            $request,
        );

        return back()->with(
            'success',
            "Password reset completed for {$account->assigned_barangay}. The account must create a private new password at its next login."
        );
    }
}
