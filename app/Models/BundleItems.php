<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleItems extends Model
{
    //protected $table = "poetry_bundles_items";
    public $timestamps = false;

    protected $fillable = [
        'bundle_id',
        'reference_id',
        'reference_type',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundles::class, 'bundle_id');
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

   /*  public function couplet()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    }

    public function couplets()
    {
        return $this->belongsTo(Couplets::class, 'couplet_id');
    } */
}
