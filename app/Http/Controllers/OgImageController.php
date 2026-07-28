<?php

namespace App\Http\Controllers;

use App\Models\Poetry;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\SindhiShaper;
use Carbon\Carbon;
use Throwable;

class OgImageController extends Controller
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;
    private const PAD = 72;
    private const AVATAR = 152;

    public function generatePoetryImage(string $slug)
    {
        try {
            $poetry = Poetry::query()
                ->with([
                    'poet.all_details',
                    'translations',
                    'category.details',
                ])
                ->where('poetry_slug', $slug)
                ->first();

            if (!$poetry) {
                return $this->fallbackPng(404);
            }

            $poet = $poetry->poet;
            $poetDetails = $poet?->all_details
                ? ($poet->all_details->firstWhere('lang', 'sd') ?? $poet->all_details->first())
                : null;

            $poetryInfo = $poetry->translations
                ? ($poetry->translations->firstWhere('lang', 'sd') ?? $poetry->translations->first())
                : null;

            $categoryName = 'شاعري';
            if ($poetry->category) {
                $sdCat = $poetry->category->details?->firstWhere('lang', 'sd')
                    ?? $poetry->category->details?->first();
                $categoryName = $sdCat?->cat_name ?? $poetry->category->slug ?? $categoryName;
            }

            $fontPath = $this->resolveFontPath();
            if ($fontPath === null) {
                Log::warning('OG image: no font available', ['slug' => $slug]);
                return $this->fallbackPng(200);
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->create(self::WIDTH, self::HEIGHT)->fill('FFFAEC');

            // Top accent
            $image->drawRectangle(0, 0, function ($rect) {
                $rect->size(self::WIDTH, 8);
                $rect->background('111111');
            });

            $contentRight = self::WIDTH - self::PAD;
            $contentWidth = self::WIDTH - (self::PAD * 2) - self::AVATAR - 40;

            // Branding (top-right)
            $branding = SindhiShaper::shape('باک');
            $this->drawText($image, $branding, $contentRight, 56, $fontPath, 34, '111111', 'right');

            $brandSub = SindhiShaper::shape('سنڌي شاعريءَ جو آرڪائيو');
            $this->drawText($image, $brandSub, $contentRight, 98, $fontPath, 22, '666666', 'right');

            // Title (strip decorative harakat — GD can't position them on presentation forms)
            $titleRaw = trim((string) ($poetryInfo?->title ?? $poetry->poetry_slug ?? 'Untitled'));
            $titleRaw = SindhiShaper::stripHarakat($titleRaw);
            $titleLines = $this->wrapSindhiLines($titleRaw, $fontPath, 64, $contentWidth);
            $titleBlockHeight = count($titleLines) * 80;
            $titleStartY = (int) ((self::HEIGHT - $titleBlockHeight) / 2) - 8;

            foreach ($titleLines as $i => $line) {
                $shaped = SindhiShaper::shape($line);
                $this->drawText(
                    $image,
                    $shaped,
                    $contentRight,
                    $titleStartY + ($i * 80),
                    $fontPath,
                    64,
                    '111111',
                    'right'
                );
            }

            // Bottom meta: circular avatar + poet + category + date
            $avatarSize = self::AVATAR;
            $avatarX = $contentRight - $avatarSize;
            $avatarY = self::HEIGHT - self::PAD - $avatarSize;
            $metaRight = $avatarX - 32;

            $avatarPath = $this->resolveAvatarPath($poet?->poet_pic ?? '');
            if ($avatarPath) {
                $this->placeCircularAvatar($manager, $image, $avatarPath, $avatarX, $avatarY, $avatarSize);
            } else {
                $image->drawCircle((int) ($avatarX + $avatarSize / 2), (int) ($avatarY + $avatarSize / 2), function ($circle) use ($avatarSize) {
                    $circle->radius((int) ($avatarSize / 2));
                    $circle->background('E8E4D8');
                    $circle->border('DDD7C8', 2);
                });
            }

            $authorName = trim((string) ($poetDetails?->poet_laqab ?? $poetDetails?->poet_name ?? $poet?->poet_slug ?? 'اڻڄاتل'));
            $this->drawText($image, SindhiShaper::shape($authorName), $metaRight, $avatarY + 48, $fontPath, 38, '111111', 'right');
            $this->drawText($image, SindhiShaper::shape($categoryName), $metaRight, $avatarY + 96, $fontPath, 26, '555555', 'right');

            $dateText = $this->formatSindhiDate($poetry->created_at);
            if ($dateText !== '') {
                $this->drawText(
                    $image,
                    SindhiShaper::shape($dateText),
                    $metaRight,
                    $avatarY + 132,
                    $fontPath,
                    22,
                    '777777',
                    'right'
                );
            }

            $response = response($image->encodeByExtension('png')->toString())
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'public, max-age=604800');

            if (request()->has('download')) {
                $filename = Str::slug($poetryInfo?->title ?? $slug) . '-baakh.png';
                $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }

            return $response;
        } catch (Throwable $e) {
            Log::error('OG image generation failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fallbackPng(200);
        }
    }

    /**
     * Solid brand-colored PNG so crawlers never see a 5xx from /og-image/*.
     */
    private function fallbackPng(int $status)
    {
        static $png = null;
        if ($png === null) {
            $png = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5+YFsAAAAASUVORK5CYII='
            );
        }

        $brand = public_path('assets/og/baakh-og-v2-1200x630.png');
        if (is_file($brand) && is_readable($brand)) {
            return response(file_get_contents($brand), $status)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'public, max-age=3600');
        }

        return response($png, $status)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function resolveFontPath(): ?string
    {
        $candidatePaths = [
            public_path('assets/fonts/SF-Arabic.ttf'),
            resource_path('fonts/SF-Arabic.ttf'),
            '/Library/Fonts/SF-Arabic.ttf',
            '/System/Library/Fonts/SFArabic.ttf',
            public_path('assets/fonts/sindhi/thar.ttf'),
            public_path('assets/fonts/NotoNastaliqUrdu-Regular.ttf'),
            '/System/Library/Fonts/Supplemental/Geeza Pro.ttf',
            '/Library/Fonts/Arial Unicode.ttf',
        ];

        foreach ($candidatePaths as $path) {
            if ($path && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function resolveAvatarPath(string $avatarPath): ?string
    {
        if ($avatarPath === '') {
            return null;
        }

        $cleanPath = ltrim(str_replace('\\', '/', $avatarPath), '/');
        $candidates = [
            public_path($cleanPath),
            public_path('storage/' . $cleanPath),
            storage_path('app/public/' . $cleanPath),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function formatSindhiDate($date): string
    {
        if (!$date) {
            return '';
        }

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

    /**
     * @return list<string> logical (unshaped) lines that fit maxWidth when shaped
     */
    private function wrapSindhiLines(string $text, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current . ' ' . $word;
            $width = $this->measureText(SindhiShaper::shape($trial), $fontPath, $fontSize);

            if ($width <= $maxWidth || $current === '') {
                $current = $trial;
                continue;
            }

            $lines[] = $current;
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        // Cap at 3 lines for OG card balance
        if (count($lines) > 3) {
            $head = array_slice($lines, 0, 2);
            $tail = implode(' ', array_slice($lines, 2));
            $head[] = $this->truncateToWidth($tail, $fontPath, $fontSize, $maxWidth);
            return $head;
        }

        return $lines;
    }

    private function truncateToWidth(string $text, string $fontPath, int $fontSize, int $maxWidth): string
    {
        $ellipsis = '…';
        if ($this->measureText(SindhiShaper::shape($text), $fontPath, $fontSize) <= $maxWidth) {
            return $text;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';
        foreach ($chars as $ch) {
            $trial = $out . $ch . $ellipsis;
            if ($this->measureText(SindhiShaper::shape($trial), $fontPath, $fontSize) > $maxWidth) {
                break;
            }
            $out .= $ch;
        }

        return rtrim($out) . $ellipsis;
    }

    private function measureText(string $text, string $fontPath, int $fontSize): int
    {
        $box = @imagettfbbox($fontSize, 0, $fontPath, $text);
        if ($box === false) {
            return 0;
        }

        return (int) abs($box[2] - $box[0]);
    }

    private function drawText(
        ImageInterface $image,
        string $text,
        int $x,
        int $y,
        string $fontPath,
        int $size,
        string $color,
        string $align = 'right'
    ): void {
        if ($text === '') {
            return;
        }

        $image->text($text, $x, $y, function ($font) use ($fontPath, $size, $color, $align) {
            $font->file($fontPath);
            $font->size($size);
            $font->color($color);
            $font->align($align);
            $font->valign('top');
        });
    }

    private function placeCircularAvatar(
        ImageManager $manager,
        ImageInterface $canvas,
        string $avatarPath,
        int $x,
        int $y,
        int $size
    ): void {
        try {
            $avatar = $manager->read($avatarPath)->cover($size, $size);
            $gdAvatar = imagecreatefromstring($avatar->encodeByExtension('png')->toString());
            if ($gdAvatar === false) {
                return;
            }

            $mask = imagecreatetruecolor($size, $size);
            imagesavealpha($mask, true);
            $transparent = imagecolorallocatealpha($mask, 0, 0, 0, 127);
            imagefill($mask, 0, 0, $transparent);

            $black = imagecolorallocate($mask, 0, 0, 0);
            imagefilledellipse($mask, (int) ($size / 2), (int) ($size / 2), $size - 2, $size - 2, $black);

            $out = imagecreatetruecolor($size, $size);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, $transparent);

            for ($py = 0; $py < $size; $py++) {
                for ($px = 0; $px < $size; $px++) {
                    $m = imagecolorat($mask, $px, $py);
                    $ma = ($m >> 24) & 0x7F;
                    if ($ma >= 120) {
                        continue;
                    }
                    $rgb = imagecolorat($gdAvatar, $px, $py);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $col = imagecolorallocatealpha($out, $r, $g, $b, 0);
                    imagesetpixel($out, $px, $py, $col);
                }
            }

            ob_start();
            imagepng($out);
            $pngData = ob_get_clean();

            imagedestroy($gdAvatar);
            imagedestroy($mask);
            imagedestroy($out);

            if ($pngData) {
                $circular = $manager->read($pngData);
                $canvas->place($circular, 'top-left', $x, $y);

                // Thin ring
                $canvas->drawCircle((int) ($x + $size / 2), (int) ($y + $size / 2), function ($circle) use ($size) {
                    $circle->radius((int) ($size / 2));
                    $circle->border('FFFFFF', 4);
                });
            }
        } catch (Throwable $e) {
            Log::debug('OG image avatar skipped', ['error' => $e->getMessage()]);
        }
    }
}
