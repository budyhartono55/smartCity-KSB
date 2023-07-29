<?php

namespace App\Repositories\Application;

use App\Repositories\Application\ApplicationInterface as ApplicationInterface;
use App\Models\Application;
use App\Models\Pilar;
use App\Http\Resources\ApplicationResource;
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


class ApplicationRepository implements ApplicationInterface
{

    protected $application;

    // Response API HANDLER
    use API_response;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    // getAll
    public function getAllApplications()
    {
        try {
            // $key = 'AllApplications_'.request()->get("page", 1);
            // if (Redis::exists($key)) {
            //     $result = json_decode(Redis::get($key));
            //     return $this->success("List Keseluruhan Application from (CACHE)", $result);
            // }

            // $application = Application::latest('created_at')->paginate(12);
            // if($application){
            //     Redis::set($key, json_encode($application));
            //     Redis::expire($key, 60); // Cache for 60 seconds

            //     return $this->success("List keseluruhan Application", $application);
            // };

            // =============
            $key = 'AllApplications_' . request()->get("page", 1);
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Application from (CACHE)", $result);
            }
            $subquery = Pilar::select('pilar_title')
                ->whereColumn('pilars.id', 'applications.pilar_id');

            $applicationWithPilar = Application::select('applications.*')
                ->selectSub($subquery, 'pilar_title')
                ->latest('created_at')
                ->paginate(12);

            if ($applicationWithPilar) {
                Redis::set($key, json_encode($applicationWithPilar));
                Redis::expire($key, 60); // Cache for 60 seconds

                return $this->success("List keseluruhan Application", $applicationWithPilar);
            };

            //========================
            //NO-REDIS
            // $application = Application::all();
            // return $this->success(" List kesuluruhan application", $application);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('application_' . $id)) {
                $result = json_decode(Redis::get('application_' . $id));
                return $this->success("Detail Application dengan ID = ($id) from (CACHE)", $result);
            };

            $application = Application::find($id);
            if ($application) {
                Redis::set('application_' . $id, json_encode($application));
                Redis::expire('application_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Application", $application);
            } else {
                return $this->error("Not Found", "Application dengan ID = ($id) tidak ditemukan!", 404);
            }

            //====================
            //NO-REDIS
            // $application = Application::find($id);
            // // Check the application
            // if(!$application) return $this->error("Application dengan ID = ($id) tidak ditemukan!", 404);
            // return $this->success("Detail application", $application);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createApplication($request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'applications_title'     =>    'required',
                'description'            =>    'required',
                'pilar_id'               =>    'required',
                'image'                  =>    'image|
                                            mimes:jpeg,png,jpg,gif,svg|
                                            max:2048',
            ],
            [
                'applications_title.required' => 'Mohon isikan application_title',
                'description.required' => 'Mohon isikan description',
                'pilar_id.required' => 'Mohon isikan pilar_id',
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
            // Buat objek Application baru
            $application = new Application();
            $application->applications_title = $request->applications_title;
            $application->description = $request->description;
            $application->url = $request->url;

            $pilar_id = $request->pilar_id;
            $pilar = DB::table('pilars')->where('id', $pilar_id)->first();
            if ($pilar) {
                $application->pilar_id = $pilar_id;
            } else {
                return $this->error("Not Found", "Pilar dengan ID = ($pilar_id) tidak ditemukan!", 404);
            }
            if ($request->hasFile('image')) {
                $destination = 'public/images';
                $t_destination = 'public/thumbnails/t_images';
                $image = $request->file('image');
                $imageName = time() . "." . $image->getClientOriginalExtension();

                $application->image = $imageName;
                //storeOriginal
                $image->storeAs($destination, $imageName);

                // compress to thumbnail 
                Helper::resizeImage($image, $imageName, $request);
            }
            // Simpan objek Application
            $create = $application->save();

            if ($create) {
                RedisHelper::deleteKeysApplication();
                return $this->success("Application Berhasil ditambahkan!", $application);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }

    // update
    public function updateApplication($request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'applications_title'     =>    'required',
                'description'            =>    'required',
                'pilar_id'               =>    'required',
                'image'                  =>    'image|
                                            mimes:jpeg,png,jpg,gif,svg|
                                            max:2048',
            ],
            [
                'applications_title.required' => 'Mohon isikan application_title',
                'description.required' => 'Mohon isikan description',
                'pilar_id.required' => 'Mohon isikan pilar_id',
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
            $application = Application::find($id);

            // Check if the application exists
            if (!$application) {
                return $this->error("Not Found", "Application dengan ID = ($id) tidak ditemukan!", 404);
            }
            // Checking Category_id
            $id = $request->pilar_id;
            $checkPilar = Pilar::find($id);
            if (!$checkPilar) {
                return $this->error("Not Found", "Pilar ID = ($id) tidak ditemukan!", 404);
            }
            // processing new image
            if ($request->hasFile('image')) {
                if ($application->image) {
                    Storage::delete('public/images/' . $application->image);
                    Storage::delete('public/thumbnails/t_images/' . $application->image);
                }
                $destination = 'public/images';
                $t_destination = 'public/thumbnails/t_images';
                $image = $request->file('image');
                $imageName = time() . "." . $image->getClientOriginalExtension();

                $application->image = $imageName;
                //storeOriginal
                $image->storeAs($destination, $imageName);

                // compress to thumbnail 
                Helper::resizeImage($image, $imageName, $request);
            } else {
                if ($request->delete_image) {
                    Storage::delete('public/images/' . $application->image);
                    Storage::delete('public/thumbnails/t_images/' . $application->image);
                    $application->image = null;
                }
                $application->image = $application->image;
            }

            // approved
            $application['applications_title'] = $request->applications_title;
            $application['description'] = $request->description;
            $application['url'] = $request->url;
            $application['pilar_id'] = $request->pilar_id;

            $update = $application->save();
            if ($update) {
                RedisHelper::deleteKeysApplication();
                return $this->success("Application Berhasil diperbaharui!", $application);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // delete
    public function deleteApplication($id)
    {
        try {
            // search
            $application = Application::find($id);
            // return dd($application);
            if (!$application) {
                return $this->error("Not Found", "Application dengan ID = ($id) tidak ditemukan!", 404);
            }
            if ($application->image) {
                Storage::delete('public/images/' . $application->image);
                Storage::delete('public/thumbnails/t_images/' . $application->image);
            }

            $del = $application->delete();
            if ($del) {
                RedisHelper::deleteKeysApplication();
                return $this->success("Application dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            // approved
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }
}
