<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doodle extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'baakh_doodles';

    protected $fillable = [
        'title',
        'image',
        'link_url',
        'start_date',
        'end_date',
        'reference_type',
        'reference_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function shouldBeDisplayedToday()
    {
        $today = Carbon::today();

        // Check doodle’s own display dates
        $doodleActive = $this->start_date <= $today && 
            (is_null($this->end_date) || $this->end_date >= $today);

        // Check reference model’s dates, if it has date_of_birth or date_of_death fields
        if ($this->reference && $this->reference_type === 'App\Models\Poets') {
            $dateOfBirth = $this->reference->date_of_birth ? Carbon::parse($this->reference->date_of_birth) : null;
            $dateOfDeath = $this->reference->date_of_death ? Carbon::parse($this->reference->date_of_death) : null;
    
            $dateMatches = ($dateOfBirth && $dateOfBirth->isBirthday()) || 
                           ($dateOfDeath && $dateOfDeath->isBirthday());
    
            return $doodleActive || $dateMatches;
        }

        return $doodleActive;
    }

}
