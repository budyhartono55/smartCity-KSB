<?php
namespace App\Repositories\Pilar;

use App\Repositories\Pilar\PilarInterface as PilarInterface;
use App\Models\Pilar;
use App\Http\Resources\PilarResource;
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


class PilarRepository implements PilarInterface{

    protected $pilar;
    
    // Response API HANDLER
    use API_response;

	public function __construct(Pilar $pilar)
	{
        $this->pilar = $pilar;  
    }

    // getAll
    public function getAllPilars()
    {
        try {
            $key = 'AllPilars_'.request()->get("page", 1);
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Pilar from (CACHE)", $result);
            }
            
            $pilar = Pilar::latest('created_at')->paginate(12);
            if($pilar){
                Redis::set($key, json_encode($pilar));
                Redis::expire($key, 60); // Cache for 60 seconds

                return $this->success("List keseluruhan Pilar", $pilar);
            };

            //========================
            //NO-REDIS
            // $pilar = Pilar::all();
            // return $this->success(" List kesuluruhan pilar", $pilar);
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('pilar_' . $id)) {
                $result = json_decode(Redis::get('pilar_' . $id));
                return $this->success("Detail Pilar dengan ID = ($id) from (CACHE)", $result);
            }

            $category = Pilar::find($id);
            if ($category) {
                Redis::set('pilar_' . $id, json_encode($category));
                Redis::expire('pilar_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Pilar", $category);
            } else {
                return $this->error("Not Found", "Pilar dengan ID = ($id) tidak ditemukan!", 404);
            }

            //====================
            //NO-REDIS
            // $pilar = Pilar::find($id);
            // // Check the pilar
            // if(!$pilar) return $this->error("Pilar dengan ID = ($id) tidak ditemukan!", 404);
            // return $this->success("Detail pilar", $pilar);
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createPilar($request)
    {
        $validator = Validator::make($request->all(), [
            'pilar_title' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'pilar_title.required' => 'Mohon isikan pilar_title',
            'image.image' => 'Pastikan file Image bertipe gambar',
            'image.mimes' => 'Format Image yang diterima hanya jpeg, png, jpg, gif, dan svg',
            'image.max' => 'File Image terlalu besar, usahakan dibawah 2MB',
        ]);
    
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }
    
        try {
            $pilar = new Pilar();
            $pilar->pilar_title = $request->pilar_title;
            $pilar->sub_dimensial = $request->sub_dimensial;
            $pilar->strategy = $request->strategy;
            $pilar->program = $request->program;
            $pilar->pengembangan_kebijakan_dan_kelembagaan = $request->pengembangan_kebijakan_dan_kelembagaan;
            $pilar->infrastruktur_pendukung = $request->infrastruktur_pendukung;
            $pilar->penguatan_literasi = $request->penguatan_literasi;
    
            if ($request->hasFile('image')) {
                $destination = 'public/images';
                $image = $request->file('image');
                $t_destination = 'public/thumbnails/t_images';
                $imageName = time() . '.' . $image->getClientOriginalExtension();
    
                $pilar->image = $imageName;
                // Store Original
                $image->storeAs($destination, $imageName);
                
                // Compress Image
                Helper::resizeImage($image, $imageName, $request);
            }
    
            $create = $pilar->save();
    
            if ($create) {
                RedisHelper::deleteKeysPilar();
                return $this->success("Pilar Berhasil ditambahkan!", $pilar);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // update
    public function updatePilar($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pilar_title' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'pilar_title.required' => 'Mohon isikan pilar_title',
            'image.image' => 'Pastikan file Image bertipe gambar',
            'image.mimes' => 'Format Image yang diterima hanya jpeg, png, jpg, gif, dan svg',
            'image.max' => 'File Image terlalu besar, usahakan dibawah 2MB',
        ]);
    
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }
                try {
                    // search
                    $pilar = Pilar::find($id);
                    
                    // Check if the pilar exists
                    if (!$pilar) {
                        return $this->error("Not Found", "Pilar dengan ID = ($id) tidak ditemukan!", 404);
                    }else{
                         // processing new image
                        if ($request->hasFile('image')) {
                            if ($pilar->image) 
                            {
                               Storage::delete('public/images/' . $pilar->image);
                               Storage::delete('public/thumbnails/t_images/' . $pilar->image);
                            }
                                $destination ='public/images';
                                $t_destination ='public/thumbnails/t_images';
                                $image = $request->file('image');
                                $imageName = time() . "." . $image->getClientOriginalExtension();
                               
                                $pilar->image = $imageName;
                                //storeOriginal
                                $image->storeAs($destination, $imageName);
                    
                                //compressImage
                                Helper::resizeImage($image, $imageName, $request);
                            }
                            if ($request->delete_image) {
                                Storage::delete('public/images/' . $pilar->image);
                                Storage::delete('public/thumbnails/t_images/' . $pilar->image);
                                $pilar->image = null;
                            }
                            $pilar->image = $pilar->image;
                    }

                    // approved
                    $pilar['pilar_title'] = $request->pilar_title;
                    $pilar['sub_dimensial'] = $request->sub_dimensial;
                    $pilar['strategy'] = $request->strategy;
                    $pilar['program'] = $request->program;
                    $pilar['pengembangan_kebijakan_dan_kelembagaan'] = $request->pengembangan_kebijakan_dan_kelembagaan;
                    $pilar['infrastruktur_pendukung'] = $request->infrastruktur_pendukung;
                    $pilar['penguatan_literasi'] = $request->penguatan_literasi;
    
                    $update = $pilar->save();
                    if ($update) {
                        RedisHelper::deleteKeysPilar();
                        return $this->success("Pilar Berhasil diperbaharui!", $pilar);
                    }
                } catch (\Exception $e) {
                    return $this->error("Internal Server Error", $e->getMessage());
                }
    }

    // delete
    public function deletePilar($id)
    {
        try {
            // search
            $pilar = Pilar::find($id);
            // return dd($pilar);
            if (!$pilar) {
                return $this->error("Not Found", "Pilar dengan ID = ($id) tidak ditemukan!", 404);
            }
                if ($pilar->image) 
                    {
                        Storage::delete('public/images/' . $pilar->image);
                        Storage::delete('public/thumbnails/t_images/' . $pilar->image);
                    }
                    
                $del = $pilar->delete();
                if ($del) {
                RedisHelper::deleteKeysPilar();
                return $this->success("Pilar dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            // approved
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    public function getAllPilarsIncludeApp()
    {
        try {
            $key = 'AllPilars_withApp_'.request()->get("page", 1);
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Pilar include Aplikasi from (CACHE)", $result);
            }
            
            $pilar = Pilar::with('application')
                            ->latest('created_at')
                            ->paginate(12);
            if($pilar){
                Redis::set($key, json_encode($pilar));
                Redis::expire($key, 60); // Cache for 60 seconds

                return $this->success("List keseluruhan Pilar include Aplikasi", $pilar);
            };

            //========================
            //NO-REDIS
            // $pilar = Pilar::all();
            // return $this->success(" List kesuluruhan pilar", $pilar);
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }
}