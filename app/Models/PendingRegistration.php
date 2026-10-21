<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A sign up whose email has not been confirmed yet; it becomes a User when the link is opened. */
class PendingRegistration extends Model
{
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
