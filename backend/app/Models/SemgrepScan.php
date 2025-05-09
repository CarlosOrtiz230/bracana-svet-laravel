<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemgrepScan extends Model
{
    protected $fillable = [
        'target_file',
        'findings',
        'raw_output',
    ];

    protected $casts = [
        'findings' => 'array',
    ];
}
