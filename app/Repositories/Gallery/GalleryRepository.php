<?php

namespace App\Repositories\Gallery;

use App\Repositories\Gallery\GalleryInterface as GalleryInterface;
use App\Models\Gallery;
use App\Models\Category;
use App\Http\Resources\GalleryResource;
use Exception;
use Illuminate\Http\Request;
use App\Traits\API_response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Helpers\RedisHelper;
use App\Helpers\Helper;
use Intervention\Image\Facades\Image;


class GalleryRepository implements GalleryInterface
{

    protected $gallery;

    // Response API HANDLER
    use API_response;

    public function __construct(Gallery $gallery)
    {
        $this->gallery = $gallery;
    }

    // getAll
    public function getAllGalleries()
    {
        try {
            $key = 'AllGalleries_' . request()->get("page", 1);
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Gallery from (CACHE)", $result);
            }

            $gallery = Gallery::latest()->paginate(12);
            if ($gallery) {
                Redis::set($key, json_encode($gallery));
                Redis::expire($key, 60); // Cache for 60 seconds

                return $this->success("List keseluruhan Gallery", $gallery);
            }

            //=====================
            //NO-REDIS
            // $gallery = Gallery::paginate(6);
            // $gallery = Gallery::latest()->paginate(12);
            // return $this->success(" List kesuluruhan gallery", $gallery);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('gallery_' . $id)) {
                $result = json_decode(Redis::get('gallery_' . $id));
                return $this->success("Detail Gallery dengan ID = ($id) from (CACHE)", $result);
            }

            $gallery = Gallery::find($id);
            if ($gallery) {
                Redis::set('gallery_' . $id, json_encode($gallery));
                Redis::expire('gallery_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Gallery", $gallery);
            } else {
                return $this->error("Not Found", "Gallery dengan ID = ($id) tidak ditemukan!", 404);
            }

            //=====================
            //NO-REDIS
            // $gallery = Gallery::find($id);
            // // Check the gallery
            // if(!$gallery) return $this->error("Gallery dengan ID = ($id) tidak ditemukan!", 404);
            // return $this->success("Detail gallery", $gallery);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createGallery($request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'gallery_title'   =>    'required',
                'image'           =>    'required|
                                    image|
                                    mimes:jpeg,png,jpg,gif,svg|
                                    max:2048',
            ],
            [
                'gallery_title.required' => 'Mohon isikan gallery_title',
                'image.required' => 'Mohon input Image',
                'image.image' => 'Pastikan file Image bertipe gambar',
                'image.mimes' => 'Format Image yang diterima hanya jpeg, png, jpg, gif dan svg',
                'image.max' => 'File Image terlalu besar, usahakan dibawah 2MB',
            ]
        );
        //check if validation fails
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }

        try {
            // Checking Category_id
            $id = $request->category_id;
            $checkCategory = Category::find($id);
            if (!$checkCategory) {
                return $this->error("Not Found", "Category ID = ($id) tidak ditemukan!", 404);
            }

            $destination = 'public/images';
            $t_destination = 'public/thumbnails/t_images';
            $image = $request->file('image');
            $imageName = time() . "." . $image->getClientOriginalExtension();

            //storeOriginal
            $image->storeAs($destination, $imageName);

            // compress to thumbnail 
            Helper::resizeImage($image, $imageName, $request);

            // Buat objek Gallery baru
            $gallery = new Gallery();
            $gallery->gallery_title = $request->gallery_title;
            $gallery->caption = $request->caption;
            $gallery->image = $imageName;
            $gallery->category_id = $request->category_id;

            // Simpan objek Gallery
            $create = $gallery->save();

            if ($create) {
                RedisHelper::deleteKeysGallery();
                return $this->success("Gallery Berhasil ditambahkan!", $gallery);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // update
    public function updateGallery($request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'gallery_title'   =>    'required',
                'image'           =>    'image|
                                        mimes:jpeg,png,jpg,gif,svg|
                                        max:2048',
            ],
            [
                'gallery_title.required' => 'Mohon isikan gallery_title',
                'image.image' => 'Pastikan file Image bertipe gambar',
                'image.mimes' => 'Format Image yang diterima hanya jpeg, png, jpg, gif dan svg',
                'image.max' => 'File Image terlalu besar, usahakan dibawah 2MB',
            ]
        );
        //check if validation fails
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }
        try {
            // search
            $gallery = Gallery::find($id);

            // Check if the gallery exists
            if (!$gallery) {
                return $this->error("Not Found", "Gallery dengan ID = ($id) tidak ditemukan!", 404);
            }
            // Checking Category_id
            $id = $request->category_id;
            $checkCategory = Category::find($id);
            if (!$checkCategory) {
                return $this->error("Not Found", "Category ID = ($id) tidak ditemukan!", 404);
            }
            // processing new image
            if ($request->hasFile('image')) {
                if ($gallery->image) {
                    Storage::delete('public/images/' . $gallery->image);
                    Storage::delete('public/thumbnails/t_images/' . $gallery->image);
                }
                $destination = 'public/images';
                $t_destination = 'public/thumbnails/t_images';
                $image = $request->file('image');
                $imageName = time() . "." . $image->getClientOriginalExtension();

                //storeOriginal
                $image->storeAs($destination, $imageName);
                $gallery->image = $imageName;

                // compress to thumbnail 
                Helper::resizeImage($image, $imageName, $request);
            } else {
                if ($request->delete_image) {
                    Storage::delete('public/images/' . $gallery->image);
                    Storage::delete('public/thumbnails/t_images/' . $gallery->image);
                    $gallery->image = null;
                }
                $gallery->image = $gallery->image;
            }
            // approved
            $gallery['gallery_title'] = $request->gallery_title;
            $gallery['caption'] = $request->caption;
            $gallery['category_id'] = $request->category_id;

            $update = $gallery->save();
            if ($update) {
                RedisHelper::deleteKeysGallery();
                return $this->success("Gallery Berhasil diperbaharui!", $gallery);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }

    // delete
    public function deleteGallery($id)
    {
        try {
            // search
            $gallery = Gallery::find($id);
            // return dd($gallery);
            if (!$gallery) {
                return $this->error("Not Found", "Gallery dengan ID = ($id) tidak ditemukan!", 404);
            }
            if ($gallery->image) {
                Storage::delete('public/images/' . $gallery->image);
                Storage::delete('public/thumbnails/t_images/' . $gallery->image);
            }

            $del = $gallery->delete();
            if ($del) {
                RedisHelper::deleteKeysGallery();
                return $this->success("Gallery dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }

            // approved
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }
}
