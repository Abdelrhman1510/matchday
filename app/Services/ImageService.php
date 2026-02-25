<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    /**
     * Upload image and generate multiple sizes
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory Directory name (e.g., 'avatars', 'cafes')
     * @return array Returns paths: ['original' => '...', 'medium' => '...', 'thumbnail' => '...']
     */
    public function upload($file, string $directory): array
    {
        // Validate file type
        $mimeType = $file->getMimeType();
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('Only JPG, PNG, and WEBP images are supported');
        }

        // Generate unique filename base
        $filenameBase = uniqid() . '_' . time();
        $extension = $this->getExtension($mimeType);

        // Create image manager with GD driver
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        // Define sizes and paths
        $sizes = [
            'original' => ['path' => "{$directory}/original/{$filenameBase}.{$extension}", 'width' => null, 'height' => null],
            'medium' => ['path' => "{$directory}/medium/{$filenameBase}.{$extension}", 'width' => 300, 'height' => 300],
            'thumbnail' => ['path' => "{$directory}/thumbnail/{$filenameBase}.{$extension}", 'width' => 150, 'height' => 150],
        ];

        $paths = [];

        foreach ($sizes as $size => $config) {
            $imageCopy = clone $image;
            
            // Resize if dimensions specified
            if ($config['width'] && $config['height']) {
                $imageCopy->cover($config['width'], $config['height']);
            }

            // Encode based on mime type
            $encoded = $this->encodeImage($imageCopy, $mimeType);

            // Store image
            Storage::disk('public')->put($config['path'], $encoded);

            // Save path for return
            $paths[$size] = $config['path'];
        }

        return $paths;
    }

    /**
     * Delete all sizes of an image
     * 
     * @param array $paths Array with keys: original, medium, thumbnail
     * @return void
     */
    public function delete(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Get file extension from mime type
     */
    private function getExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /**
     * Encode image based on mime type
     */
    private function encodeImage($image, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => $image->toJpeg(85),
            'image/png' => $image->toPng(),
            'image/webp' => $image->toWebp(85),
            default => $image->toJpeg(85),
        };
    }
}
