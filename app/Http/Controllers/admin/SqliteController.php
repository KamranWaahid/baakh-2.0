<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use Illuminate\Http\Request;

class SqliteController extends Controller
{
    public function index()
    {

    }


    /**
     * Generate Tables
     */
    public function generateTable($tableName)
    {
        // search

        if($tableName === 'poetry')
        {
            $res = $this->regenratePoetry();
        }
    }

    /**
     * Regenrate Poetry
     */
    public function regenratePoetry()
    {
        // fetch all from DB and store into the SQLite
        
    }
}
