<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiktoScan extends Model
{
    protected $fillable = [
        'target_url',
        'findings',
        'raw_output',
    ];

    protected $casts = [
        'findings' => 'array',
    ];
}
