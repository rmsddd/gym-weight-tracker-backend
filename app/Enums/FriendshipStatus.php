<?php

namespace App\Enums;

// A request waits for the other person to accept it; accepted, both see each other's workouts.
enum FriendshipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
}
