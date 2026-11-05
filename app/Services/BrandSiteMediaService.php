<?php
namespace App\Services;

use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class BrandSiteMediaService
{
    public function store(Store $store, ?UploadedFile $image, string $bytes = '', ?string $contentType = null): string
    {
        if ($image) {
            Validator::make(['image' => $image], [
                'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            ])->validate();

            $path = $image->store('brandsite/'.$store->id, 'public');
            abort_unless($path, 500, 'Could not store this image.');
            return $path;
        }

        abort_unless(strlen($bytes) <= 5 * 1024 * 1024, 413, 'Choose an image smaller than 5 MB.');
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $dimensions = @getimagesizefromstring($bytes);
        $mime = $dimensions['mime'] ?? null;
        if (!isset($extensions[$mime]) || $mime !== $contentType || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image' => 'Choose a valid JPG, PNG or WebP image up to 6000 pixels per side.',
            ]);
        }

        $path = 'brandsite/'.$store->id.'/'.bin2hex(random_bytes(20)).'.'.$extensions[$mime];
        abort_unless(\Illuminate\Support\Facades\Storage::disk('public')->put($path, $bytes), 500, 'Could not store this image.');
        return $path;
    }
}