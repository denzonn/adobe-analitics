<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmailAccount extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'email',
        'google_id',
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->user();
    }

    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Scope query ke akun milik user tertentu.
     */
    public function scopeOwnedBy($query, ?User $user)
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $user->getKey());
    }
}
