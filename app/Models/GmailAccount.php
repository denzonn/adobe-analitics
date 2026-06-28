<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmailAccount extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'email',
        'google_id',
        'access_token',
        'refresh_token',
    ];

    public function emails()
    {
        return $this->hasMany(Email::class);
    }
}
