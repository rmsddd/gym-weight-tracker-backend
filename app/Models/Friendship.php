<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A friend request from requester to addressee, or a friendship once the addressee accepts it. */
class Friendship extends Model
{
    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => FriendshipStatus::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /** The row between two users, whichever of them sent the request. */
    public function scopeBetween(Builder $query, int $userId, int $otherId): void
    {
        $query->where(fn ($pair) => $pair
            ->where(fn ($q) => $q->where('requester_id', $userId)->where('addressee_id', $otherId))
            ->orWhere(fn ($q) => $q->where('requester_id', $otherId)->where('addressee_id', $userId)));
    }

    public function scopeInvolving(Builder $query, int $userId): void
    {
        $query->where(fn ($q) => $q->where('requester_id', $userId)->orWhere('addressee_id', $userId));
    }

    public function isPending(): bool
    {
        return $this->status === FriendshipStatus::Pending;
    }

    public function otherThan(int $userId): int
    {
        return $this->requester_id === $userId ? $this->addressee_id : $this->requester_id;
    }
}
