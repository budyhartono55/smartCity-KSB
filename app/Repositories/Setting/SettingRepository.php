<?php

namespace App\Repositories\Setting;

use App\Helpers\Helper;
use App\Models\Setting;
use App\Repositories\Setting\SettingInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Traits\API_response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SettingRepository implements SettingInterface
{
    // 1 Day redis expired
    private $expired = 86400;
    private $destinationImage = "images";
    private $destinationImageThumbnail = "thumbnails/t_images";
    private $setting;
    use API_response;

    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }


    public function getAll()
    {
        try {
            $keyOne = "setting-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Setting from (CACHE)", $result);
            }
            $data = Setting::first();;
            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
            return $this->success("List Data Setting", $data);


            // No REDIS
            // $data = Setting::all();
            // return $this->success(
            //     " List semua data Setting",
            //     $data
            // );
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    // findOne
    public function getById($id)
    {
        try {
            $keyOne = "setting-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Contact dengan ID = ($id) from (CACHE)", $result);
            }

            $data = Setting::find($id);
            if (!empty($data)) {
                Redis::set($keyOne, json_encode($data));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                return $this->success("Setting Dengan ID = ($id)", $data);
            }
            return $this->error("Not Found", "Setting dengan ID = ($id) tidak ditemukan!", 404);

            // NO REDIS
            // $data = Setting::find($id);

            // // Check the user
            // if (!$data) return $this->error("Setting dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail Setting", $data);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'web_title'     => 'required',
            'caption'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }


        try {
            $fileName = $request->hasFile('image') ? time() . "." . $request->image->getClientOriginalExtension() : "";
            $data = [
                'web_title' => $request->web_title,
                'caption' => $request->caption,
                'address' => $request->address,
                'contact' => $request->contact,
                'facebook' => $request->facebook,
                'instagram' => $request->instagram,
                'twitter' => $request->twitter,
                'youtube' => $request->youtube,
                'image' => $fileName,
                'edited_by' => Auth::user()->id,
                'created_by' => Auth::user()->id
            ];
            $add = Setting::create($data);

            if ($add) {
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                    Helper::resizeImage($image, $fileName, $request);
                }
                Helper::deleteRedis("setting-*");
                return $this->success("Setting Berhasil ditambahkan!", [$data]);
            }
            return $this->error("FAILED", "Setting Gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'web_title'     => 'required',
            'caption'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            // search
            $datas = Setting::find($id);

            // check
            if (!$datas) {
                return $this->error("Not Found", "Setting dengan ID = ($id) tidak ditemukan!", 404);
            } else {
                // dd($request->web_title);
                $datas['web_title'] = $request->web_title;
                $datas['caption'] = $request->caption;
                $datas['contact'] = $request->contact;
                $datas['address'] = $request->address;
                $datas['facebook'] = $request->facebook;
                $datas['instagram'] = $request->instagram;
                $datas['twitter'] = $request->twitter;
                $datas['youtube'] = $request->youtube;
                $datas['edited_by'] = Auth::user()->id;
                $datas['updated_by'] = Auth::user()->id;

                // check image if exist
                if ($request->hasFile('image')) {
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
                // $this->setting->update($data,$id);
                if ($datas->save()) {
                    Helper::deleteRedis("setting-*");
                    return $this->success("Setting Berhasil diperharui!", [$datas]);
                }
                return $this->error("FAILED", "Setting Gagal diperharui!", 400);
            }
        } catch (Exception $e) {

            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = Setting::find($id);
            if (!$data) {
                return $this->error("Not Found", "Setting dengan ID = ($id) tidak ditemukan!", 404);
            }
            // delete image
            Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $data->image);

            // approved
            if ($data->delete()) {
                Helper::deleteRedis("setting-*");
                return $this->success("Setting dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "Setting dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {

            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
