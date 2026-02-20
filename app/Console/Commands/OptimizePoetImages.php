<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Poets;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;

class OptimizePoetImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poets:optimize-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize all existing poet images to 250x250 webp format.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $poets = Poets::whereNotNull('poet_pic')->get();
        $count = $poets->count();
        $this->info("Found $count poets with images to optimize.");

        $success = 0;
        $failed = 0;

        foreach ($poets as $poet) {
            $cleanedPic = ltrim($poet->poet_pic, '/');
            $oldPath = public_path($cleanedPic);

            if (!file_exists($oldPath)) {
                $this->warn("File not found for {$poet->poet_slug}: {$poet->poet_pic}");
                $failed++;
                continue;
            }

            // Skip if it's already a well-formatted webp of intended size, though it's safer to just re-optimize
            // all of them to ensure they are properly resized and compressed.

            try {
                $image = Image::read($oldPath);

                // Maximum displayed size is 200x200 (in PoetProfile.jsx).
                // Let's resize it down to 250x250 to allow slightly high-res displays while keeping it small
                $image->cover(250, 250, 'center');

                // Prepare new webp path
                $folderPath = "assets/images/poets";
                $destination = public_path($folderPath);

                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }

                $imageName = Str::slug($poet->poet_slug) . '_' . uniqid() . '_opt.webp';
                $newRelativePath = "$folderPath/$imageName";
                $newFullPath = "$destination/$imageName";

                $image->toWebp(80)->save($newFullPath);

                // Delete old image if it's different
                if ($oldPath !== $newFullPath && file_exists($oldPath)) {
                    try {
                        unlink($oldPath);

                        // Try to clean up those useless old small/medium thumbnails too
                        $pathInfo = pathinfo($oldPath);
                        $oldThumbSmall = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_small.' . $pathInfo['extension'];
                        $oldThumbMed = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_medium.' . $pathInfo['extension'];
                        if (file_exists($oldThumbSmall))
                            @unlink($oldThumbSmall);
                        if (file_exists($oldThumbMed))
                            @unlink($oldThumbMed);

                    } catch (\Exception $e) {
                        $this->warn("Could not delete old file for {$poet->poet_slug}: " . $e->getMessage());
                    }
                }

                $poet->update(['poet_pic' => $newRelativePath]);
                $this->info("Optimized and changed database path for {$poet->poet_slug} => {$newRelativePath}");
                $success++;

            } catch (\Exception $e) {
                $this->error("Failed to process {$poet->poet_slug}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Optimization complete. Success: $success, Failed: $failed");
        return 0;
    }
}
