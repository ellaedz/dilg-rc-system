<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Security defaults mirror the additive database migration so newly created
     * model instances behave safely before they are reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
        'failed_login_attempts' => 0,
        'session_version' => 1,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'assigned_barangay',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'failed_login_attempts',
        'last_failed_login_at',
        'locked_until',
        'session_version',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'immutable_datetime',
            'failed_login_attempts' => 'integer',
            'last_failed_login_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'session_version' => 'integer',
        ];
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function securityAuditEventsAsActor(): HasMany
    {
        return $this->hasMany(SecurityAuditEvent::class, 'actor_user_id');
    }

    public function securityAuditEventsAsTarget(): HasMany
    {
        return $this->hasMany(SecurityAuditEvent::class, 'target_user_id');
    }
}
