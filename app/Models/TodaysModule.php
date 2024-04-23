<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodaysModule extends Model
{
    
    protected $table = "today_modules";

    protected $fillable = [
        'date_today',
        'table_name',
        'table_id',
        'lang'
    ];


    public function ghazal($locale){
        $thisday = date('Y-m-d');

        $todayModule = TodaysModule::where('date_today', '=', $thisday)
            ->where('table_name', '=', 'poetry_main')
            ->first();

        if ($todayModule) {

            $poetry = Poetry::with([
                'info' => function ($query) use ($locale) {
                    $query->where('lang', $locale)->take(1); // Load only one translation
                },
                'all_couplets' => function ($query) use ($locale) {
                    $query->where('lang', $locale); // Load all couplets with the specified language
                },
                'poet.details' => function ($query) use ($locale) {
                    $query->where('lang', $locale);
                }
            ])
            ->where(['poetry_slug' => $todayModule->table_id, 'visibility' => 1])
            ->first();
        
            return $poetry;
        }

        return null; // Handle other cases or return value if needed
    }


}
