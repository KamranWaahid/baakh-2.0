<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemError extends Model
{
    protected $fillable = [
        'message',
        'code',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'user_agent',
        'ip',
        'user_id',
        'status',
        'severity',
        'environment',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
