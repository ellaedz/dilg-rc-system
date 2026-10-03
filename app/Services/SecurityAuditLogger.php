<?php

namespace App\Services;

use App\Enums\SecurityAuditAction;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityAuditLogger
{
    public function record(
        SecurityAuditAction $action,
        ?User $actor,
        ?User $target,
        ?Request $request = null,
    ): SecurityAuditEvent {
        $userAgent = $request?->userAgent();

        return SecurityAuditEvent::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'target_user_id' => $target?->getKey(),
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => is_string($userAgent) && $userAgent !== ''
                ? mb_substr($userAgent, 0, 512)
                : null,
        ]);
    }
}
