<?php

namespace App\Repositories\Profile;

use App\Helpers\Helper;
use App\Models\Profile;
use App\Repositories\Profile\ProfileInterface;
use App\Traits\API_response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class ProfileRepository implements ProfileInterface
{
    private $profile;
    // 1 Day redis expired
    private $expired = 86400;
    private $destinationImage = "images";
    private $destinationImageThumbnail = "thumbnails/t_images";
    use API_response;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }


    public function getAll()
    {
        try {
            $keyOne = "profile-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Profile from (CACHE)", $result);
            }
            $data = Profile::latest('created_at')->paginate(10);
            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data Profile", $data);

            // $data = Profile::latest('created_at')->paginate(10);

            // return $this->success(
            //     " List semua data Profile",
            //     $data
            // );
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
    // findOne
    public function getById($id)
    {
        try {
            $keyOne = "profile-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Profile dengan ID = ($id) from (CACHE)", $result);
            }

            $data = Profile::find($id);
            if (!empty($data)) {
                Redis::set($keyOne, json_encode($data));
                Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
                return $this->success("Profile Dengan ID = ($id)", $data);
            }
            return $this->error("Not Found", "Profile dengan ID = ($id) tidak ditemukan!", 404);

            // $data = Profile::find($id);

            // // Check the data
            // if (!$data) return $this->error("Profile dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail Profile", $data);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'description'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:1000'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            $fileName = $request->hasFile('image') ? time() . "." . $request->image->getClientOriginalExtension() : "";

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'image' => $fileName,
                'created_by' => Auth::user()->id
            ];
            // Create Profile
            $add = Profile::create($data);

            if ($add) {
                // Storage::disk(['public' => 'profile'])->put($fileName, file_get_contents($request->image));
                // Save Image in Storage folder profile
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                    Helper::resizeImage($image, $fileName, $request);
                }
                Helper::deleteRedis("profile-*");
                return $this->success("Profile Berhasil ditambahkan!", $data);
            }
            return $this->error("FAILED", "Profile gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'description'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:1000'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }
        try {
            // search
            $datas = Profile::find($id);
            // check
            if (!$datas) {
                return $this->error("Not Found", "Profile dengan ID = ($id) tidak ditemukan!", 404);
            }
            $datas['title'] = $request->title;
            $datas['description'] = $request->description;
            $datas['updated_by'] = Auth::user()->id;

            if ($request->hasFile('image')) {
                // public storage
                // delete image
                Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $datas->image);

                // Image name
                $fileName = time() . "." . $request->image->getClientOriginalExtension();

                $datas['image'] = $fileName;

                // Image save in public folder
                $image = $request->file('image');
                $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                Helper::resizeImage($image, $fileName, $request);
            } else {
                if ($request->delete_image) {
                    // Old image delete
                    Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $datas->image);

                    $datas['image'] = null;
                }
                $datas['image'] = $datas->image;
            }

            // update datas
            if ($datas->save()) {
                Helper::deleteRedis("profile-*");
                return $this->success("Profile Berhasil diperbaharui!", $datas);
            }
            return $this->error("FAILED", "Profile Gagal diperbaharui!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = Profile::find($id);
            if (!$data) {
                return $this->error("Not Found", "Profile dengan ID = ($id) tidak ditemukan!", 404);
            }
            // delete image
            Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $data->image);

            // approved
            if ($data->delete()) {
                Helper::deleteRedis("profile-*");
                return $this->success("Profile dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "Profile dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
