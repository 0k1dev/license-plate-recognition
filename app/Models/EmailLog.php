<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'to',
        'subject',
        'content',
        'status',
        'template_key',
        'error',
        'user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
