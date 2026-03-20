<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'organization_id',
    'inviter_user_id',
    'accepted_by_user_id',
    'invitee_email',
    'role',
    'token',
    'status',
    'expires_at',
    'last_sent_at',
    'accepted_at',
    'revoked_at',
])]
class Invitation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING->value;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAsExpiredIfNeeded(): void
    {
        if (! $this->isPending() || ! $this->isExpired()) {
            return;
        }

        $this->status = InvitationStatus::EXPIRED->value;
        $this->save();
    }
}
