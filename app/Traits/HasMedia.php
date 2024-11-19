<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
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

    public function uploadImage($file, $folderPath, $customName = null, $thumbnail = false) {
        // Validate file size and dimensions
        $maxSize = 10 * 1024 * 1024; // 10 MB in bytes
        $minWidth = 10;
        $minHeight = 10;
    
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        if ( $file->getSize() && $file->getSize() > $maxSize) {
             return ['error' => true, 'message' => 'File size exceeds the maximum allowed size (10 MB or '.$maxSize.' bytes). Your image size is '.$file->getSize()];
        }

        $image = Image::read($file);
        $width = $image->width();
        $height = $image->height();


        if ($width < $minWidth || $height < $minHeight) {
            return ['error' => true, 'message' => 'Image dimensions must be at least 10x10 pixels.'];
        }

        // check if there is custom name
        if($customName) {
            $imageName = $customName . '.' . $file->getClientOriginalExtension();
        }else{
            // Generate random name for the image
            $imageName = date('dmY') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        }

        // Upload original image
        $fullPath = $folderPath ? "assets/images/$folderPath/$imageName" : "assets/images/$imageName";
        $destination = public_path($folderPath ? "assets/images/$folderPath" : "assets/images");

        // Debug: Ensure the destination directory exists
        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        $file->move($destination, $imageName);


        /**
         * Make Thumbnail check if it is enabled
         */
        $resizedImages = [];
        if($thumbnail === true) {
            $cropSize = config('admin_media.sizes');
            foreach ($cropSize as $key => $value) {
                $resizedName = pathinfo($imageName, PATHINFO_FILENAME) . "_{$key}." . $file->getClientOriginalExtension();
                //$image->setResolution($x_resolution, $y_resolution);
                $image->scale($value['width'], $value['height'])->save(public_path("assets/images/$folderPath/$resizedName")); 
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
    public function updateImage($file, $folderPath, $oldImageUri, $customName = null, $thumbnail = false) {
        
        if(file_exists($oldImageUri)) {
             Log::info('====== image exists and deleting for ' . $customName);
            // Get the original extension of the image file
            $extension = pathinfo($oldImageUri, PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($oldImageUri, PATHINFO_FILENAME);
              
            // delink all thumbnails also [150x150, 300x300, 1024x1024]
            $thumbnailArray = config('admin_media.sizes');
            
            foreach ($thumbnailArray as $key => $thumbnailSize) {

                $thumbnailImage = 'assets/images/'.$folderPath.'/'. $filenameWithoutExtension . '_' . $key . '.' . $extension;
                
                if(file_exists($thumbnailImage)) {
                    unlink($thumbnailImage);
                }
            }
            unlink($oldImageUri);
            return $this->uploadImage($file, $folderPath, $customName, $thumbnail);
        }else{
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
    public function deleteImageFiles($oldImageUri, $hasThumbnails = false) {
        if(!file_exists($oldImageUri)) {
            return ['error' => true, 'message' => 'File does not exists in the folder']; 
        }
         
        if($hasThumbnails === true) {
            $cropSize = config('admin_media.sizes');
            
            $extension = pathinfo($oldImageUri, PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($oldImageUri, PATHINFO_FILENAME);
            $folderPath = pathinfo($oldImageUri, PATHINFO_DIRNAME);

            foreach ($cropSize as $key => $value) {
                $thumbnailImage = $folderPath.'/'. $filenameWithoutExtension . '_' . $key . '.' . $extension;
              
              if(file_exists($thumbnailImage)) {
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
    public function deleteFolderFromDirectory($path, $folderName) {
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

    public function deleteFolderRecursive($path) {
        if (is_dir($path)) {
            $files = glob($path . '/*');
            foreach ($files as $file) {
                is_dir($file) ? $this->deleteFolderRecursive($file) : unlink($file);
            }
            rmdir($path);
        }
    }
    

}
