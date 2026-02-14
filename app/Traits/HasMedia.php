<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;



trait HasMedia
{

    /**
     * Handle Uploading Images
     * $file == $request->file('name')
     * $folderPaht = $request->input('folder_id') {get folder name with ID and store image inside that folder, if it is 0 then upload image on default folder}
     * max size will be 10 MB
     * min height and width = 10x10
     * crop sizes = [150x150, 300x300, 1024x1024]
     * cropped images should be center crop 
     * -- image name structure --
     * defaultPath = storage/images
     * folderPath = storage/images/$folderName
     * name = date{dmY}_random
     * name_resized = date{dmY}_random_100x50 i.e cropped size
     * 
     * @return array ['size', 'full_path', 'file_name , 'mime_type' , 'resized_images ]
     */

    public function uploadImage($file, $folderPath, $customName = null, $thumbnail = false)
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

        // Upload original image
        $fullPath = $folderPath ? "assets/images/$folderPath/$imageName" : "assets/images/$imageName";
        $destination = public_path($folderPath ? "assets/images/$folderPath" : "assets/images");

        // Debug: Ensure the destination directory exists
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        // Save as WebP
        $image->toWebp(80)->save($destination . '/' . $imageName);


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
                $thumb->scale($value['width'], $value['height'])->toWebp(80)->save(public_path("assets/images/$folderPath/$resizedName"));
                $resizedImages[] = "assets/images/$folderPath/$resizedName";
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
     * @param [string] $file
     * @param [string] $folderPath
     * @param [string] $oldImageUri
     * @return array ['size', 'full_path', 'file_name , 'mime_type' , 'resized_images ]
     */
    public function updateImage($file, $folderPath, $oldImageUri, $customName = null, $thumbnail = false)
    {

        if (file_exists($oldImageUri)) {
            Log::info('====== image exists and deleting for ' . $customName);
            // Get the original extension of the image file
            $extension = pathinfo($oldImageUri, PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($oldImageUri, PATHINFO_FILENAME);

            // delink all thumbnails also [150x150, 300x300, 1024x1024]
            $thumbnailArray = config('admin_media.sizes');

            foreach ($thumbnailArray as $key => $thumbnailSize) {

                $thumbnailImage = 'assets/images/' . $folderPath . '/' . $filenameWithoutExtension . '_' . $key . '.' . $extension;

                if (file_exists($thumbnailImage)) {
                    unlink($thumbnailImage);
                }
            }
            unlink($oldImageUri);
            return $this->uploadImage($file, $folderPath, $customName, $thumbnail);
        } else {
            return $this->uploadImage($file, $folderPath, $customName, $thumbnail);
        }
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
        if (!file_exists($oldImageUri)) {
            return ['error' => true, 'message' => 'File does not exists in the folder'];
        }

        if ($hasThumbnails === true) {
            $cropSize = config('admin_media.sizes');

            $extension = pathinfo($oldImageUri, PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($oldImageUri, PATHINFO_FILENAME);
            $folderPath = pathinfo($oldImageUri, PATHINFO_DIRNAME);

            foreach ($cropSize as $key => $value) {
                $thumbnailImage = $folderPath . '/' . $filenameWithoutExtension . '_' . $key . '.' . $extension;

                if (file_exists($thumbnailImage)) {
                    unlink($thumbnailImage);
                }
            }

        }

        unlink($oldImageUri);
        return ['error' => false, 'message' => 'Files removed from directory'];
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
