<?php

namespace App\Repositories\Service;

use App\Repositories\Service\ServiceInterface as ServiceInterface;
use App\Models\Service;
use App\Http\Resources\ServiceResource;
use Exception;
use Illuminate\Http\Request;
use App\Traits\API_response;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ServiceRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Helpers\RedisHelper;
use App\Helpers\Helper;
use Intervention\Image\Facades\Image;

class ServiceRepository implements ServiceInterface
{

    protected $service;

    // Response API HANDLER
    use API_response;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    // getAll
    public function getAllServices()
    {
        try {

            $key = 'AllServices_' . request()->get("page", 1);
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Layanan from (CACHE)", $result);
            }

            $layanan = Service::latest('created_at')->paginate(12);
            if ($layanan) {
                Redis::set($key, json_encode($layanan));
                Redis::expire($key, 60); // Cache for 60 seconds

                return $this->success("List keseluruhan Layanan", $layanan);
            }

            //==================
            // No-REDIS
            // $layanan = Service::paginate(3);
            // return $this->success("List keseluruhan Layanan", $layanan);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    public function getAllServiceByKeyword($keyword)
    {
        try {
            if (Redis::exists('service_' . $keyword)) {
                $result = json_decode(Redis::get('service_' . $keyword));
                return $this->success("List Layanan dengan keyword = ($keyword) from (CACHE)", $result);
            }

            // If not, perform the query and cache the result in Redis
            $result = Service::where('layanan_title', 'LIKE', '%' . $keyword . '%')->get();
            if (count($result)) {
                Redis::set('service_' . $keyword, json_encode($result));
                Redis::expire('service_' . $keyword, 60); // Cache for 1 minute
                return $this->success("List Layanan dengan keyword = ($keyword)", $result);
            } else {
                return $this->error("Not Found", "Layanan dengan keyword = ($keyword) tidak ditemukan!", 404);
            }

            //==================
            //NO-REDIS
            // $result = Service::where('layanan_title', 'LIKE', '%' . $keyword . '%')->get();
            // if (count($result)) {
            //     //  return Response()->json($result);
            //     return $this->success("Detail Layanan", $result);
            // } else {
            //     return $this->error("Layanan dengan keyword = ($keyword) tidak ditemukan!", 404);
            // }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('service_' . $id)) {
                $result = json_decode(Redis::get('service_' . $id));
                return $this->success("Detail Layanan dengan ID = ($id) from (CACHE)", $result);
            }

            $layanan = Service::find($id);
            if ($layanan) {
                Redis::set('service_' . $id, json_encode($layanan));
                Redis::expire('service_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Layanan", $layanan);
            } else {
                return $this->error("Not Found", "Layanan dengan ID = ($id) tidak ditemukan!", 404);
            }

            //===================
            // NO-REDIS
            // Check the user
            // $layanan = Service::find($id);
            // if(!$layanan) return $this->error("Layanan dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail layanan", $layanan);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createService($request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'layanan_title' =>  'required',
                'icon'          =>  'required|
                                    image|
                                    mimes:jpeg,png,jpg,gif,svg|
                                    max:2048',
            ],
            [
                'layanan_title.required' => 'Mohon isikan layanan_title',
                'icon.required' => 'Mohon input Icon',
                'icon.image' => 'Pastikan file Icon bertipe gambar',
                'icon.mimes' => 'Format Icon yang diterima hanya jpeg, png, jpg, gif dan svg',
                'icon.max' => 'File Icon terlalu besar, usahakan dibawah 2MB',
            ]
        );

        //check if validation fails
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }

        try {
            $destination = 'public/icons';
            $t_destination = 'public/thumbnails/t_icons';
            $icon = $request->file('icon');
            $iconName = time() . "." . $icon->getClientOriginalExtension();

            //storeOriginal
            $icon->storeAs($destination, $iconName);

            // compress to thumbnail 
            Helper::resizeIcon($icon, $iconName, $request);

            $layanan = new Service();
            $layanan->layanan_title = $request->layanan_title;
            $layanan->url = $request->url;
            $layanan->description = $request->description;
            $layanan->icon = $iconName;
            $layanan->slug = Str::slug($request->layanan_title, '-');

            $user = Auth::user();
            $layanan->created_by = $user->id;

            $create = $layanan->save();

            if ($create) {
                RedisHelper::deleteKeysService();
                return $this->success("Layanan Berhasil ditambahkan!", $layanan);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // update
    public function updateService($request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'layanan_title' =>     'required',
                'icon'           =>    'image|
                                    mimes:jpeg,png,jpg,gif,svg|
                                    max:2048',
            ],
            [
                'layanan_title.required' => 'Mohon isikan layanan_title',
                'icon.image' => 'Pastikan file Icon bertipe gambar',
                'icon.mimes' => 'Format Icon yang diterima hanya jpeg, png, jpg, gif dan svg',
                'icon.max' => 'File Icon terlalu besar, usahakan dibawah 2MB',
            ]
        );

        //check if validation fails
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }

        try {

            // search
            $layanan = Service::find($id);

            // checkID
            if (!$layanan) {
                return $this->error("Not Found", "Layanan dengan ID = ($id) tidak ditemukan!", 404);
            }

            if ($request->hasFile('icon')) {
                //checkImage
                if ($layanan->icon) {
                    Storage::delete('public/icons/' . $layanan->icon);
                    Storage::delete('public/thumbnails/t_icons/' . $layanan->icon);
                }
                $destination = 'public/icons';
                $t_destination = 'public/thumbnails/t_icons';
                $icon = $request->file('icon');
                $iconName = time() . "." . $icon->getClientOriginalExtension();
                // dd($iconName);

                $layanan->icon = $iconName;
                //storeOriginal
                $icon->storeAs($destination, $iconName);

                // compress to thumbnail 
                Helper::resizeIcon($icon, $iconName, $request);
            } else {
                if ($request->delete_image) {
                    Storage::delete('public/icons/' . $layanan->icon);
                    Storage::delete('public/thumbnails/t_icons/' . $layanan->icon);
                    $layanan->icon = null;
                }
                $layanan->icon = $layanan->icon;
            }


            // approved
            $layanan['layanan_title'] = $request->layanan_title;
            $layanan['url'] = $request->url;
            $layanan['description'] = $request->description;
            $layanan['slug'] =  Str::slug($request->layanan_title, '-');
            $user = Auth::user();
            $layanan['created_by'] = $user->id;

            //save
            $update = $layanan->save();
            if ($update) {
                RedisHelper::deleteKeysService();
                return $this->success("Layanan Berhasil diperbaharui!", $layanan);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }


    // delete
    public function deleteService($id)
    {

        try {
            // search
            $layanan = Service::find($id);
            if (!$layanan) {
                return $this->error("Not Found", "Layanan dengan ID = ($id) tidak ditemukan!", 404);
            }
            if ($layanan->icon) {
                Storage::delete('public/icons/' . $layanan->icon);
                Storage::delete('public/thumbnails/t_icons/' . $layanan->icon);
            }
            // approved
            $del = $layanan->delete();
            if ($del) {
                RedisHelper::deleteKeysService();
                return $this->success("Layanan dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }
}
