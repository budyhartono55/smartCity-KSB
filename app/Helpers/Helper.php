<?php

namespace  App\Helpers;


use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use app\Models\Pemohon;

class Helper
{

    public static function deleteRedis($keyword)
    {
        $check = Redis::keys("*" . $keyword);
        if ($check) {
            foreach ($check as $key) {
                $keyRedis = str_replace(env('REDIS_KEY'), "", $key);
                Redis::del($keyRedis);
            }
        }
    }

    public static function resizeImage($image, $fileName, $request)
    {
        $destination = 'public/thumbnails/t_images';
        self::resizeImageProses($image, $fileName, $request, $destination);
    }
    public static function resizeIcon($icon, $fileName, $request)
    {
        $destination = 'public/thumbnails/t_icons';
        self::resizeImageProses($icon, $fileName, $request, $destination);
    }
    private static function resizeImageProses($image, $fileName, $request, $destination)
    {
        if ($image->getSize() > 100 * 100) {
            $compressedImage = Image::make($image);
            $originalWidth = $compressedImage->width();
            $originalHeight = $compressedImage->height();
            $imageFormat = $compressedImage->mime();

            $quality = $request->input('quality', 50);
            while ($compressedImage->filesize() > 100 * 100 && $quality >= 10) {
                $compressedImage->resize($originalWidth * 0.5, $originalHeight * 0.5);

                $compressedImage->encode($imageFormat, $quality);
                $quality -= 5;
            }

            Storage::put($destination . '/' . $fileName, $compressedImage->stream());
        } else {
            $image->storeAs($destination, $fileName);
        }
    }

    public static function deleteImage($destinationImage, $destinationImageThumbnail, $fileName)
    {
        $storage = Storage::disk('public');
        if ($storage->exists($destinationImage . "/" . $fileName)) {
            $storage->delete($destinationImage . "/" . $fileName);
            $storage->delete($destinationImageThumbnail . "/" . $fileName);
        }
    }


    public static function isAdmin()
    {
        return auth()->user()->level == "Admin" ? true : false;
    }
    public static function isUser()
    {
        return auth()->user()->level == "User" ? true : false;
    }
}
