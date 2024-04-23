<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set the database name
        $databaseName = config('database.connections.mysql.database');

        // Get a list of all tables in the database
        $tables = DB::select("SHOW TABLES FROM $databaseName");

        // Loop through each table and convert it to InnoDB
        foreach ($tables as $table) {
            $tableName = reset($table);
            DB::statement("ALTER TABLE `$tableName` ENGINE = InnoDB");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         
    }
};
