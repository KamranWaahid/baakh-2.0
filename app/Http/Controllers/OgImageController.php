<?php

namespace App\Http\Controllers;

use App\Models\Poetry;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use App\Helpers\SindhiShaper;

class OgImageController extends Controller
{
    public function generatePoetryImage(string $slug)
    {
        // 1. Fetch Data
        $poetry = Poetry::where('poetry_slug', $slug)->firstOrFail();
        $poet = $poetry->poet;

        // Find Sindhi translations specifically for author and poetry info
        $poetDetails = $poet->details->where('lang', 'sd')->first() ?? $poet->details->first();
        $poetryInfo = $poetry->info->where('lang', 'sd')->first() ?? $poetry->info->first();
        $category = $poetry->category;
        $categoryDetail = $category->detail->where('lang', 'sd')->first() ?? $category->detail->first();

        // 2. Setup Manager
        $manager = new ImageManager(new Driver());

        // 3. Create Canvas (1200x630)
        $image = $manager->create(1200, 630)->fill('FFFAEC');

        // 4. Paths
        $fontPath = public_path('assets/fonts/sindhi/thar.ttf');
        $avatarPath = public_path($poet->poet_pic);

        // 5. Draw Header Branding
        $branding = SindhiShaper::shape('باک: سنڌي شاعريءَ جو آرڪائيو');
        $image->text($branding, 1140, 80, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(28);
            $font->color('333333');
            $font->align('right');
        });

        // 6. Draw Poetry Title
        $titleRaw = $poetryInfo->title ?? 'Untitled';
        $title = SindhiShaper::shape($titleRaw);

        $image->text($title, 1140, 240, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(72);
            $font->color('000000');
            $font->align('right');
        });

        // 7. Draw Author Info
        $authorName = $poetDetails->poet_laqab ?? $poet->poet_slug;
        $categoryName = $categoryDetail->cat_name ?? 'شاعري';
        $metaTextRaw = $authorName . ' جو ' . $categoryName;
        $metaText = SindhiShaper::shape($metaTextRaw);

        $image->text($metaText, 1140, 480, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(48);
            $font->color('222222');
            $font->align('right');
        });

        $dateRaw = date('d اپريل، Y', strtotime($poetry->created_at));
        $dateText = SindhiShaper::shape($dateRaw);
        $image->text($dateText, 1140, 550, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(34);
            $font->color('555555');
            $font->align('right');
        });

        // 8. Draw Author Avatar (Bottom Right)
        if (file_exists($avatarPath)) {
            $avatar = $manager->read($avatarPath);
            $avatar->cover(140, 140);
            $image->place($avatar, 'bottom-right', 40, 40);
        }

        // Return as response
        return response($image->encodeByExtension('png')->toString())
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=604800');
    }
}
