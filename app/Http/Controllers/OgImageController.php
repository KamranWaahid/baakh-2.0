<?php

namespace App\Http\Controllers;

use App\Models\Poetry;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use App\Helpers\SindhiShaper;
use Carbon\Carbon;
use RuntimeException;

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
        $categoryDetail = $category?->detail?->where('lang', 'sd')->first() ?? $category?->detail?->first();

        // 2. Setup Manager
        $manager = new ImageManager(new Driver());

        // 3. Create Canvas (1200x630)
        $image = $manager->create(1200, 630)->fill('FFFAEC');

        // 4. Paths
        $fontPath = $this->resolveFontPath();
        $avatarPath = $this->resolveAvatarPath($poet?->poet_pic ?? '');

        // 5. Draw Header Branding
        $branding = SindhiShaper::shape('باک: سنڌي شاعريءَ جو آرڪائيو');
        $image->text($branding, 1140, 80, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(28);
            $font->color('333333');
            $font->align('right');
        });

        // 6. Draw Poetry Title
        $titleRaw = $poetryInfo?->title ?? $poetry->poetry_slug ?? 'Untitled';
        $title = SindhiShaper::shape($titleRaw);

        $image->text($title, 1140, 240, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(72);
            $font->color('000000');
            $font->align('right');
        });

        // 7. Draw Author Info
        $authorName = $poetDetails?->poet_laqab ?? $poet?->poet_slug ?? 'اڻڄاتل';
        $categoryName = $categoryDetail?->cat_name ?? 'شاعري';
        $metaTextRaw = $authorName . ' جو ' . $categoryName;
        $metaText = SindhiShaper::shape($metaTextRaw);

        $image->text($metaText, 1140, 480, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(48);
            $font->color('222222');
            $font->align('right');
        });

        $dateRaw = $this->formatSindhiDate($poetry->created_at);
        $dateText = SindhiShaper::shape($dateRaw);
        $image->text($dateText, 1140, 550, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(34);
            $font->color('555555');
            $font->align('right');
        });

        // 8. Draw Author Avatar (Bottom Right)
        if ($avatarPath && file_exists($avatarPath)) {
            $avatar = $manager->read($avatarPath);
            $avatar->cover(140, 140);
            $image->place($avatar, 'bottom-right', 40, 40);
        }

        // Return as response
        $response = response($image->encodeByExtension('png')->toString())
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=604800');

        if (request()->has('download')) {
            $filename = Str::slug($poetryInfo?->title ?? $slug) . '-baakh.png';
            $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return $response;
    }

    private function resolveFontPath(): string
    {
        $candidatePaths = [
            public_path('assets/fonts/NotoNastaliqUrdu-Regular.ttf'),
            public_path('assets/fonts/sindhi/thar.ttf'),
            public_path('assets/fonts/SF-Arabic.ttf'),
            resource_path('fonts/SF-Arabic.ttf'),
            '/System/Library/Fonts/Supplemental/Geeza Pro.ttf',
            '/System/Library/Fonts/Supplemental/Noto Nastaliq Urdu.ttf',
            '/Library/Fonts/Arial Unicode.ttf',
        ];

        foreach ($candidatePaths as $path) {
            if ($path && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        throw new RuntimeException('No readable Sindhi/Arabic TTF font found for OG image generation.');
    }

    private function resolveAvatarPath(string $avatarPath): ?string
    {
        if ($avatarPath === '') {
            return null;
        }

        $cleanPath = '/' . ltrim($avatarPath, '/');
        $publicCandidate = public_path(ltrim($cleanPath, '/'));

        if (is_file($publicCandidate)) {
            return $publicCandidate;
        }

        $storageCandidate = storage_path('app/public/' . ltrim($avatarPath, '/'));
        if (is_file($storageCandidate)) {
            return $storageCandidate;
        }

        return null;
    }

    private function formatSindhiDate($date): string
    {
        $months = [
            1 => 'جنوري',
            2 => 'فيبروري',
            3 => 'مارچ',
            4 => 'اپريل',
            5 => 'مئي',
            6 => 'جون',
            7 => 'جولائي',
            8 => 'آگسٽ',
            9 => 'سيپٽمبر',
            10 => 'آڪٽوبر',
            11 => 'نومبر',
            12 => 'ڊسمبر',
        ];

        $carbon = Carbon::parse($date);
        $month = $months[(int) $carbon->format('n')] ?? $carbon->format('M');

        return $carbon->format('d') . ' ' . $month . '، ' . $carbon->format('Y');
    }
}
