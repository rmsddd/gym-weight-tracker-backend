<?php

namespace App\Enums;

// Only the code is stored; the drawings live in the frontend (src/data/avatars.js).
enum Avatar: string
{
    case Athlete = 'athlete';
    case Andrei = 'andrei';
    case Grandpa = 'grandpa';
    case Dino = 'dino';
    case Bear = 'bear';
    case Lion = 'lion';
    case Robot = 'robot';
    case Dog = 'dog';
    case Tiger = 'tiger';
    case Skeleton = 'skeleton';
}
