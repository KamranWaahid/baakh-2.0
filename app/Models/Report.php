<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['user_id', 'poem_id', 'poet_id', 'url', 'reason', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poetry()
    {
        return $this->belongsTo(Poetry::class, 'poem_id');
    }

    public function poet()
    {
        return $this->belongsTo(Poets::class, 'poet_id'); // Assuming model name is Poets based on previous context
    }
}
