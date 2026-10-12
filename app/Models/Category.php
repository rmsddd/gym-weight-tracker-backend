<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** The one category that is not a muscle group; its exercises are logged in minutes. */
    public const CARDIO = 'Cardio';

    protected $fillable = [
        'name',
    ];

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
