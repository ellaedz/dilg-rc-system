<?php

namespace App\Services;

use App\Http\Middleware\EnsureSecuritySession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecuritySessionManager
{
    public function establish(Request $request, User $user): void
    {
        $request->session()->put(
            EnsureSecuritySession::SESSION_VERSION_KEY,
            (int) $user->session_version,
        );
    }

    public function invalidateAll(User $user): void
    {
        $this->deleteDatabaseSessions($user);
    }

    public function invalidateOthersAndRegenerate(Request $request, User $user): void
    {
        $this->deleteDatabaseSessions($user, $request->session()->getId());
        $request->session()->regenerate();
        $this->establish($request, $user);
    }

    private function deleteDatabaseSessions(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey());

        if (filled($exceptSessionId)) {
            $query->where('id', '!=', $exceptSessionId);
        }

        $query->delete();
    }
}
