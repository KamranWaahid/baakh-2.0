<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserComments extends Model
{
    use HasFactory;
    protected $table = 'users_comments';
    protected $fillable = [
        'user_id',
        'poetry_id',
        'comment'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id',  'user_id');
    }
}
