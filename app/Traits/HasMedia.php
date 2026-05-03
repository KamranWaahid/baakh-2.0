<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;



trait HasMedia
{
    private function mediaDisk(): string
    {
        return (string) config('media.disk', 'local');
    }

    private function mediaBasePath(): string
    {
        return trim((string) config('media.base_path', 'assets/images'), '/');
    }

    private function isCloudDisk(): bool
    {
        $disk = $this->mediaDisk();
        return !in_array($disk, ['local', 'public'], true);
    }

    private function buildRelativePath(string $folderPath, string $fileName): string
    {
        $base = $this->mediaBasePath();
        $folder = trim($folderPath, '/');
        return $folder !== '' ? "{$base}/{$folder}/{$fileName}" : "{$base}/{$fileName}";
    }

    private function resolveStoragePath(?string $uri): ?string
    {
        if (!$uri) {
            return null;
        }

        $value = trim($uri);
        if ($value === '') {
            return null;
        }

        $base = $this->mediaBasePath() . '/';

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = ltrim((string) parse_url($value, PHP_URL_PATH), '/');
            if (str_contains($path, $base)) {
                return substr($path, strpos($path, $base));
            }
            return null;
        }

        $path = ltrim($value, '/');
        return str_starts_with($path, $base) ? $path : null;
    }

    /**
     * Handle Uploading Images
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folderPath
     * @param string|null $customName
     * @param bool $thumbnail
     * @return array{error: bool, message?: string, size?: int, file_name?: string, full_path?: string, mime_type?: string, resized_images?: array}
     */
    public function uploadImage($file, string $folderPath, ?string $customName = null, bool $thumbnail = false): array
    {
        // Validate file size and dimensions
        $maxSize = 10 * 1024 * 1024; // 10 MB in bytes
        $minWidth = 10;
        $minHeight = 10;

        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        if ($file->getSize() && $file->getSize() > $maxSize) {
            return ['error' => true, 'message' => 'File size exceeds the maximum allowed size (10 MB or ' . $maxSize . ' bytes). Your image size is ' . $file->getSize()];
        }

        $image = Image::read($file);
        $width = $image->width();
        $height = $image->height();


        if ($width < $minWidth || $height < $minHeight) {
            return ['error' => true, 'message' => 'Image dimensions must be at least 10x10 pixels.'];
        }

        // check if there is custom name
        if ($customName) {
            $imageName = Str::slug($customName) . '.webp';
        } else {
            // Generate random name for the image
            $imageName = date('dmY') . '_' . uniqid() . '.webp';
        }

        $relativePath = $this->buildRelativePath($folderPath, $imageName);
        $encoded = (string) $image->toWebp(80);

        if ($this->isCloudDisk()) {
            Storage::disk($this->mediaDisk())->put($relativePath, $encoded, [
                'visibility' => 'public',
                'ContentType' => 'image/webp',
            ]);
            $fullPath = Storage::disk($this->mediaDisk())->url($relativePath);
        } else {
            $destination = public_path(dirname($relativePath));
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            file_put_contents(public_path($relativePath), $encoded);
            $fullPath = $relativePath;
        }


        /**
         * Make Thumbnail check if it is enabled
         */
        $resizedImages = [];
        if ($thumbnail === true) {
            $cropSize = config('admin_media.sizes');
            foreach ($cropSize as $key => $value) {
                // Resize and Save as WebP
                $resizedName = pathinfo($imageName, PATHINFO_FILENAME) . "_{$key}.webp";
                // Note: calling scale() modifies the instance, so we should clone or re-read if doing multiple?
                // Intervention v3 is immutable? No, modifiers usually return new instance or modify?
                // Documentation says: $image->scale(...) returns the modified image.
                // If we do $image->scale(..)->save(..), $image is now scaled.
                // So subsequent iterations will try to scale the ALREADY SCALED image. 
                // We must use the original image for each resize.
                // Intervention Image v3 objects are mutable.
                // We should clone the original image for each resize.

                // However, the original code had:
                // $image->scale(...)->save(...)
                // And it was in a foreach loop! This was a BUG in the original code if v2/v3 is mutable!
                // Or maybe they relied on the fact that they only had one size?
                // Wait, previous code:
                // foreach ($cropSize as $key => $value) { 
                //    $image->scale(...)->save(...) 
                // }
                // If $cropSize has multiple sizes, the second iteration would scale the result of the first.
                // PROBABLY A BUG. I should fix this by re-reading or cloning.

                // Let's use $image (which is original resolution) and clone it?
                // Image::read($file) creates new instance.
                // Or I can re-read from $file inside loop? Or simpler: clone.
                // Validation: does `clone $image` work in V3? Yes.

                $thumb = clone $image;
                $thumbEncoded = (string) $thumb->scale($value['width'], $value['height'])->toWebp(80);
                $thumbRelativePath = $this->buildRelativePath($folderPath, $resizedName);

                if ($this->isCloudDisk()) {
                    Storage::disk($this->mediaDisk())->put($thumbRelativePath, $thumbEncoded, [
                        'visibility' => 'public',
                        'ContentType' => 'image/webp',
                    ]);
                    $resizedImages[] = Storage::disk($this->mediaDisk())->url($thumbRelativePath);
                } else {
                    file_put_contents(public_path($thumbRelativePath), $thumbEncoded);
                    $resizedImages[] = $thumbRelativePath;
                }
            }
        }


        return [
            'error' => false,
            'size' => $fileSize,
            'file_name' => $imageName,
            'full_path' => $fullPath,
            'mime_type' => $mimeType,
            'resized_images' => $resizedImages
        ];
    }

    /**
     * Update Image function is used to delete old image and upload new image
     * This function deletes all images (including thumbnails)
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folderPath
     * @param string $oldImageUri
     * @param string|null $customName
     * @param bool $thumbnail
     * @return array
     */
    public function updateImage($file, string $folderPath, ?string $oldImageUri, ?string $customName = null, bool $thumbnail = false): array
    {
        if ($oldImageUri) {
            $this->deleteImageFiles($oldImageUri, $thumbnail);
        }

        return $this->uploadImage($file, $folderPath, $customName, $thumbnail);
    }


    /**
     * Show Image function is used to display image with its detail
     *
     * @param string $file
     * @param array $class
     * @param array $styles
     * @param string $caption
     * @return html <img>
     */

    /**
     * Delete Image Files
     */
    public function deleteImageFiles($oldImageUri, $hasThumbnails = false)
    {
        $storagePath = $this->resolveStoragePath((string) $oldImageUri);
        if (!$storagePath) {
            return ['error' => true, 'message' => 'Could not resolve media path'];
        }

        $paths = [$storagePath];
        if ($hasThumbnails === true) {
            $cropSize = config('admin_media.sizes');
            $extension = pathinfo($storagePath, PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($storagePath, PATHINFO_FILENAME);
            $folderPath = pathinfo($storagePath, PATHINFO_DIRNAME);

            foreach ($cropSize as $key => $value) {
                $paths[] = $folderPath . '/' . $filenameWithoutExtension . '_' . $key . '.' . $extension;
            }
        }

        if ($this->isCloudDisk()) {
            Storage::disk($this->mediaDisk())->delete($paths);
            return ['error' => false, 'message' => 'Files removed from cloud storage'];
        }

        foreach ($paths as $path) {
            $absolutePath = public_path($path);
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        return ['error' => false, 'message' => 'Files removed from local storage'];
    }


    /**
     * Delete Folders 
     *
     * @param string $path
     * @param string $folderName
     * @return void
     */
    public function deleteFolderFromDirectory($path, $folderName)
    {
        if (is_dir($path)) {
            $files = glob($path . '/*');
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $subFolderName = basename($file);
                    if ($subFolderName === $folderName) {
                        $this->deleteFolderRecursive($file);
                    }
                }
            }
        }
    }

    public function deleteFolderRecursive($path)
    {
        if (is_dir($path)) {
            $files = glob($path . '/*');
            foreach ($files as $file) {
                is_dir($file) ? $this->deleteFolderRecursive($file) : unlink($file);
            }
            rmdir($path);
        }
    }


}
