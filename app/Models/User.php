<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens,HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $attributes = [
        'avatar' => 'dino',
    ];

    /**
     * Rules for a new username, given already lowercased: it is stored and compared that way. It is
     * not fillable, since it can only be set once (on sign up, or by an account that has none).
     */
    public static function usernameRules(): array
    {
        return ['required', 'string', 'min:3', 'max:20', 'regex:/^[a-z0-9._]+$/', 'unique:users,username'];
    }

    /** The same wording wherever a username is chosen: on sign up and on the "choose one" page. */
    public static function usernameMessages(): array
    {
        return [
            'username.required' => 'Choose a username.',
            'username.min' => 'A username has at least 3 characters.',
            'username.max' => 'A username has at most 20 characters.',
            'username.regex' => 'Use only letters, digits, dots and underscores.',
            'username.unique' => 'This username is taken.',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Spatie's relations: the role and permissions are sent in a clean shape where needed
        'roles',
        'permissions',
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
        ];
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /** The ids of everyone this user is friends with, whichever of them sent the request. */
    public function friendIds(): Collection
    {
        return Friendship::involving($this->id)
            ->where('status', FriendshipStatus::Accepted)
            ->get(['requester_id', 'addressee_id'])
            ->map(fn (Friendship $friendship) => $friendship->otherThan($this->id));
    }

    public function isFriendsWith(User $other): bool
    {
        return Friendship::between($this->id, $other->id)
            ->where('status', FriendshipStatus::Accepted)
            ->exists();
    }
}
