<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeTagsAndCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baakh:wipe-tags-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all tags and categories from the database and poetry records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('no-interaction') && !$this->confirm('This will DELETE all tags and categories and clear them from all poetry records. Do you wish to continue?')) {
            $this->info('Command cancelled.');
            return;
        }

        $this->info('Wiping tags and categories...');

        try {
            DB::beginTransaction();

            // Disable foreign key checks for SQLite/MySQL
            Schema::disableForeignKeyConstraints();

            // Clear Poetry tags and categories
            DB::table('poetry_main')->update([
                'category_id' => null,
                'poetry_tags' => null
            ]);

            // Truncate tables
            DB::table('baakh_tags')->truncate();

            if (Schema::hasTable('tags_translations')) {
                DB::table('tags_translations')->truncate();
            }

            DB::table('categories')->truncate();

            if (Schema::hasTable('category_details')) {
                DB::table('category_details')->truncate();
            }

            Schema::enableForeignKeyConstraints();

            DB::commit();
            $this->info('Data wiped successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during wipe: ' . $e->getMessage());
        }
    }
}
