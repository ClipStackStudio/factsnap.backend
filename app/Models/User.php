<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_guest',
        'is_premium',
        // firebase auth fields
        'firebase_uid',
        'phone_number',
        'phone_verified_at',
        'last_logged_in_at',
        // push notification fields
        'apns_device_token',
        'notification_preferences',
        'push_notifications_enabled',
        // notification settings
        'timezone',
        'quiet_hours_start',
        'quiet_hours_end',
        'quiet_hours_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'phone_verified_at' => 'datetime',
            'last_logged_in_at' => 'datetime',
            'notification_preferences' => 'array',
            'password' => 'hashed',
            'is_guest' => 'boolean',
            'is_premium' => 'boolean',
            'push_notifications_enabled' => 'boolean',
            'quiet_hours_enabled' => 'boolean',
        ];
    }

    /**
     * Get the packages that the user has subscribed to.
     *
     * @return BelongsToMany
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'user_packages')
            ->withPivot('subscribed_at', 'delivery_frequency', 'delivery_time', 'delivery_days', 'next_delivery_at', 'push_enabled')
            ->withTimestamps();
    }

    /**
     * Get the delivered facts for this user.
     */
    public function deliveredFacts()
    {
        return $this->hasMany(DeliveredFact::class);
    }
}
