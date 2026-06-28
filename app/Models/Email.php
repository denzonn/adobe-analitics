<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $table = 'emails';

    // Tipe email Adobe yang dikenali untuk statistik.
    public const TYPE_SUBMISSION_UPDATE = 'submission_update';
    public const TYPE_EARNINGS_REPORT  = 'earnings_report';

    protected $fillable = [
        'gmail_account_id',
        'gmail_message_id',
        'sender',
        'subject',
        'snippet',
        'email_type',
        'accepted_count',
        'pending_count',
        'rejected_count',
        'earnings_amount',
        'earnings_currency',
        'is_read',
        'body',
        'received_at',
    ];

    protected $casts = [
        'is_read'         => 'boolean',
        'received_at'     => 'datetime',
        'accepted_count'  => 'integer',
        'pending_count'   => 'integer',
        'rejected_count'  => 'integer',
        'earnings_amount' => 'decimal:4',
    ];

    public function account()
    {
        return $this->belongsTo(GmailAccount::class, 'gmail_account_id');
    }

    public function isSubmissionUpdate(): bool
    {
        return $this->email_type === self::TYPE_SUBMISSION_UPDATE;
    }

    public function isEarningsReport(): bool
    {
        return $this->email_type === self::TYPE_EARNINGS_REPORT;
    }
}