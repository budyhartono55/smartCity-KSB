<?php

namespace App\Repositories\Infrastruktur;

use App\Helpers\Helper;
use App\Models\Infrastruktur;
use App\Repositories\Infrastruktur\InfrastrukturInterface;
use App\Traits\API_response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class InfrastrukturRepository implements InfrastrukturInterface
{
    private $infrastruktur;
    // 1 Day redis expired
    private $expired = 86400;
    private $destinationImage = "images";
    private $destinationImageThumbnail = "thumbnails/t_images";
    use API_response;

    public function __construct(Infrastruktur $infrastruktur)
    {
        $this->infrastruktur = $infrastruktur;
    }


    public function getAll()
    {
        try {
            $keyOne = "infrastruktur-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Infrastruktur from (CACHE)", $result);
            }
            $data = Infrastruktur::latest('created_at')->paginate(10);
            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data Infrastruktur", $data);

            // $data = Infrastruktur::latest('created_at')->paginate(10);

            // return $this->success(
            //     " List semua data Infrastruktur",
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
            $keyOne = "infrastruktur-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Infrastruktur dengan ID = ($id) from (CACHE)", $result);
            }

            $data = Infrastruktur::find($id);
            if (!empty($data)) {
                Redis::set($keyOne, json_encode($data));
                Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
                return $this->success("Infrastruktur Dengan ID = ($id)", $data);
            }
            return $this->error("Not Found", "Infrastruktur dengan ID = ($id) tidak ditemukan!", 404);

            // $data = Infrastruktur::find($id);

            // // Check the data
            // if (!$data) return $this->error("Infrastruktur dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail Infrastruktur", $data);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'image'           => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2000'
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
            // Create Infrastruktur
            $add = Infrastruktur::create($data);

            if ($add) {
                // Storage::disk(['public' => 'infrastruktur'])->put($fileName, file_get_contents($request->image));
                // Save Image in Storage folder infrastruktur
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                    Helper::resizeImage($image, $fileName, $request);
                }
                Helper::deleteRedis("infrastruktur-*");
                return $this->success("Infrastruktur Berhasil ditambahkan!", $data);
            }
            return $this->error("FAILED", "Infrastruktur gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:2000'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }
        try {
            // search
            $datas = Infrastruktur::find($id);
            // check
            if (!$datas) {
                return $this->error("Not Found", "Infrastruktur dengan ID = ($id) tidak ditemukan!", 404);
            }
            $datas['title'] = $request->title;
            $datas['description'] = $request->description;
            $datas['updated_by'] = Auth::user()->id;

            if ($request->hasFile('image')) {
                // public storage
                $storage = Storage::disk('public');

                // Old image delete
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
                Helper::deleteRedis("infrastruktur-*");
                return $this->success("Infrastruktur Berhasil diperbaharui!", $datas);
            }
            return $this->error("FAILED", "Infrastruktur Gagal diperbaharui!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = Infrastruktur::find($id);
            if (!$data) {
                return $this->error("Not Found", "Infrastruktur dengan ID = ($id) tidak ditemukan!", 404);
            }
            // Old image delete
            Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $data->image);

            // approved
            if ($data->delete()) {
                Helper::deleteRedis("infrastruktur-*");
                return $this->success("Infrastruktur dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "Infrastruktur dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
