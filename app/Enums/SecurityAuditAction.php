<?php

namespace App\Enums;

enum SecurityAuditAction: string
{
    case PasswordReset = 'password_reset';
    case PasswordChanged = 'password_changed';
    case PasswordChangeRequired = 'password_change_required';
    case LoginFailed = 'login_failed';
    case AccountLocked = 'account_locked';
    case AccountUnlocked = 'account_unlocked';
}
