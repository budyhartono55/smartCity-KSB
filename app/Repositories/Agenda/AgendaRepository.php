<?php

namespace App\Repositories\Agenda;

use App\Repositories\Agenda\AgendaInterface as AgendaInterface;
use App\Models\Agenda;
use App\Models\User;
use App\Models\Category;
use App\Http\Resources\AgendaResource;
use Exception;
use Illuminate\Http\Request;
use App\Traits\API_response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Helpers\RedisHelper;
use App\Helpers\Helper;
use Intervention\Image\Facades\Image;




class AgendaRepository implements AgendaInterface
{

    protected $agenda;

    // Response API HANDLER
    use API_response;

    public function __construct(Agenda $agenda)
    {
        $this->agenda = $agenda;
    }

    // getAll
    public function getAllAgendas()
    {
        try {
            $check = Auth::check();
            if ($check) {

                $user_level = Auth::user()->level;
                $user_id = Auth::id();

                $user = User::find($user_id);
                if ($user_level == "Admin") {
                    //key1
                    $keyAll = 'AllAgendas_' . request()->get("page", 1);
                    if (Redis::exists($keyAll)) {
                        $result = json_decode(Redis::get($keyAll));
                        return $this->success("List Keseluruhan Agenda from (CACHE)", $result);
                    }
                    $agenda = Agenda::latest('posted_at')->paginate(12);
                    Redis::set($keyAll, json_encode($agenda));
                    Redis::expire($keyAll, 60); // Cache for 60 seconds
                    return $this->success("List kesuluruhan agenda", $agenda);
                };
                //key2
                $keyOne = 'AllAgendasByUser_' . request()->get("page", 1);
                $user_id = Auth::id();
                // dd($user_id);
                if (Redis::exists($keyOne)) {
                    $result = json_decode(Redis::get($keyOne));
                    return $this->success("List kesuluruhan agenda berdasarkan user_id = ($user_id) from (CACHE)", $result);
                }
                $agendas = Agenda::whereHas('user', function ($query) use ($user) {
                    $query->where('id', $user->id);
                })->latest()->paginate(12);
                Redis::set($keyOne, json_encode($agendas));
                Redis::expire($keyOne, 60); // Cache for 60 seconds
                return $this->success("List kesuluruhan agenda berdasarkan user_id = ($user_id)", $agendas);
            };
            //public
            $keyAll = 'AllAgendas_' . request()->get("page", 1);
            if (Redis::exists($keyAll)) {
                $result = json_decode(Redis::get($keyAll));
                return $this->success("List Keseluruhan Agenda from (CACHE)", $result);
            }
            $agenda = Agenda::latest('posted_at')->paginate(12);
            Redis::set($keyAll, json_encode($agenda));
            Redis::expire($keyAll, 60); // Cache for 60 seconds
            return $this->success("List kesuluruhan agenda", $agenda);

            //==================
            //NO-REDIS
            //     $check = Auth::check();
            //     //dd($user);
            // if(!$check){
            //     $agenda = Agenda::latest('posted_at')->paginate(12);
            //     return $this->success("List kesuluruhan agenda", $agenda);
            // };
            //     $user_level = Auth::user()->level;
            //     $user_id = Auth::id();

            //     $user = User::find($user_id); // Ganti dengan ID pengguna yang sesuai
            //     // dd($user);
            //     if($user_level == 1){
            //         $agenda = Agenda::latest('posted_at')->paginate(12);
            //         return $this->success("List kesuluruhan agenda", $agenda);
            //     };
            //         $agendas = Agenda::whereHas('user', function ($query) use ($user) {
            //             $query->where('id', $user->id);
            //         })->latest()->paginate(3);
            //         return $this->success("List kesuluruhan agenda berdasarkan user_id = ($user_id)", $agendas);
            //============
            // $agenda = Agenda::latest()->paginate(3);
            //  $agendas = User::with('agendas')->get();
            //         return $this->success("List kesuluruhan Agenda include User", $agendas);


        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    //filterByCategory
    public function getAllAgendaByCategoryId($id)
    {
        try {
            if (Redis::exists('agenda_' . $id)) {
                $result = json_decode(Redis::get('agenda_' . $id));
                return $this->success("Detail AgendaByCategory dengan ID = ($id) from (CACHE)", $result);
            }

            $checkCategory = Category::find($id);
            if ($checkCategory) {
                $agenda = DB::table('agendas')->where('category_id', $id)->paginate(12);
                Redis::set('agenda_' . $id, json_encode($agenda));
                Redis::expire('agenda_' . $id, 60); // Cache for 1 minute
                return $this->success("List Agenda By Category", $agenda);
            } else {
                return $this->error("Not Found", "Agenda dengan Category ID = ($id) tidak ditemukan!", 404);
            }

            //==================
            //NO-REDIS
            // $checkCategory = Category::find($id);
            // if (!$checkCategory) {
            //     return $this->error("Agenda dengan Category ID = ($id) tidak ditemukan!", 404);
            // }
            // $agenda = DB::table('agendas')->where('category_id', $id)->paginate(10);
            // return $this->success("List Agenda By Category", $agenda);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    //searchByKeywords
    public function getAllAgendaByKeyword($keyword)
    {
        try {
            if (Redis::exists('agenda_' . $keyword)) {
                $result = json_decode(Redis::get('agenda_' . $keyword));
                return $this->success("List Agenda dengan keyword = ($keyword) from (CACHE)", $result);
            }

            $result = Agenda::where('agenda_title', 'LIKE', '%' . $keyword . '%')->get();
            if (count($result)) {
                Redis::set('agenda_' . $keyword, json_encode($result));
                Redis::expire('agenda_' . $keyword, 60); // Cache for 1 minute
                return $this->success("List Agenda dengan keyword = ($keyword)", $result);
            } else {
                return $this->error("Not Found", "Agenda dengan keyword = ($keyword) tidak ditemukan!", 404);
            }

            //==============
            //NO-REDIS
            // $result = Agenda::where('agenda_title', 'LIKE', '%' . $keyword . '%')->get();
            // if (count($result)) {
            //     //  return Response()->json($result);
            //     return $this->success("Detail agenda", $result);
            // } else {
            //     return $this->error("Agenda dengan keyword = ($keyword) tidak ditemukan!", 404);
            // }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function showBySlug($slug)
    {
        try {
            if (Redis::exists('agenda_' . $slug)) {
                $result = json_decode(Redis::get('agenda_' . $slug));
                return $this->success("List Agenda dengan slug = ($slug) from (CACHE)", $result);
            }

            $slug = Str::slug($slug);
            $agenda = Agenda::where('slug', 'LIKE', '%' . $slug . '%')->first();
            if ($agenda) {
                Redis::set('agenda_' . $slug, json_encode($agenda));
                Redis::expire('agenda_' . $slug, 60); // Cache for 1 minute
                return $this->success("Detail Agenda", $agenda);
            } else {
                return $this->error("Not Found", "Agenda dengan ID = ($slug) tidak ditemukan!", 404);
            }

            //==================
            // NO-REDIS
            // $agenda = Agenda::find($slug);
            // $slug = Str::slug($slug); 
            // $agenda = Agenda::where('slug', 'LIKE','%' . $slug . '%')->first();
            // // Check the agenda
            // if (!$agenda) return $this->error("Agenda dengan slug = ($slug) tidak ditemukan!", 404);

            return $this->success("Detail agenda", $agenda);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('agenda_' . $id)) {
                $result = json_decode(Redis::get('agenda_' . $id));
                return $this->success("Detail Agenda dengan ID = ($id) from (CACHE)", $result);
            }

            $agenda = Agenda::find($id);
            if ($agenda) {
                Redis::set('agenda_' . $id, json_encode($agenda));
                Redis::expire('agenda_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Agenda", $agenda);
            } else {
                return $this->error("Not Found", "Agenda dengan ID = ($id) tidak ditemukan!", 404);
            }

            //================
            //NO-REDIS
            // $agenda = Agenda::find($id);
            // // Check the agenda
            // if (!$agenda) return $this->error("Agenda dengan ID = ($id) tidak ditemukan!", 404);
            // return $this->success("Detail agenda", $agenda);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createAgenda($request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'agenda_title'    =>    'required',
                'image'           =>    'image|
                                        mimes:jpeg,png,jpg,gif,svg|
                                        max:2048',
            ],
            [
                'agenda_title.required' => 'Mohon isikan agenda_title',
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
            $agenda = new Agenda();
            $agenda->agenda_title = $request->agenda_title;
            $agenda->description = $request->description;
            $agenda->slug =  Str::slug($request->agenda_title, '-');
            $agenda->hold_on = Carbon::createFromFormat('d-m-Y', $request->hold_on);
            $user = Auth::user();
            $agenda->user_id = $user->id;
            $agenda->posted_at = Carbon::now();

            $category_id = $request->category_id;
            $category = DB::table('categories')->where('id', $category_id)->first();
            if ($category) {
                $agenda->category_id = $category_id;
            } else {
                return $this->error("Not Found", "Kategori dengan ID = ($category_id) tidak ditemukan!", 404);
            }

            if ($request->hasFile('image')) {
                $destination = 'public/images';
                $t_destination = 'public/thumbnails/t_images';
                $image = $request->file('image');
                $imageName = time() . "." . $image->getClientOriginalExtension();

                $agenda->image = $imageName;
                //storeOriginal
                $image->storeAs($destination, $imageName);

                // compress to thumbnail 
                Helper::resizeImage($image, $imageName, $request);
            }

            // Simpan objek Agenda
            $create = $agenda->save();

            if ($create) {
                RedisHelper::deleteKeysAgenda();
                return $this->success("Agenda Berhasil ditambahkan!", $agenda);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // update
    public function updateAgenda($request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'agenda_title'    =>    'required',
                'image'           =>    'image|
                                    mimes:jpeg,png,jpg,gif,svg|
                                    max:2048',
            ],
            [
                'agenda_title.required' => 'Mohon isikan agenda_title',
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
            $agenda = Agenda::find($id);
            // dd($request->delete_image);
            // Check if the agenda exists
            if (!$agenda) {
                return $this->error("Not Found", "Agenda dengan ID = ($id) tidak ditemukan!", 404);
            }
            // Checking Category_id
            $id = $request->category_id;
            $checkCategory = Category::find($id);
            if (!$checkCategory) {
                return $this->error("Not Found", "Kategori ID = ($id) tidak ditemukan!", 404);
            }

            // processing new image
            if ($request->hasFile('image')) {
                if ($agenda->image) {
                    Storage::delete('public/images/' . $agenda->image);
                    Storage::delete('public/thumbnails/t_images/' . $agenda->image);
                }

                $destination = 'public/images';
                $t_destination = 'public/thumbnails/t_images';
                $image = $request->file('image');
                $imageName = time() . "." . $image->getClientOriginalExtension();

                $agenda->image = $imageName;
                //storeOriginal
                $image->storeAs($destination, $imageName);

                //compressImage
                Helper::resizeImage($image, $imageName, $request);
            } else {
                if ($request->delete_image) {
                    Storage::delete('public/images/' . $agenda->image);
                    Storage::delete('public/thumbnails/t_images/' . $agenda->image);
                    $agenda->image = null;
                }
                $agenda->image = $agenda->image;
            }

            // approved
            $agenda['agenda_title'] = $request->agenda_title;
            $agenda['description'] = $request->description;
            $agenda['hold_on'] = Carbon::createFromFormat('d-m-Y', $request->hold_on);
            $agenda['category_id'] = $request->category_id;
            $agenda['slug'] =  Str::slug($request->agenda_title, '-');

            $oldPostedAt = $agenda->posted_at;
            $agenda['posted_at'] = $oldPostedAt;

            $update = $agenda->save();
            if ($update) {
                RedisHelper::deleteKeysAgenda();
                return $this->success("Agenda Berhasil diperbaharui!", $agenda);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }

    // delete
    public function deleteAgenda($id)
    {
        try {

            // search
            $agenda = Agenda::find($id);
            // return dd($agenda);
            if (!$agenda) {
                return $this->error("Not Found", "Agenda dengan ID = ($id) tidak ditemukan!", 404);
            }
            if ($agenda->image) {
                Storage::delete('public/images/' . $agenda->image);
                Storage::delete('public/thumbnails/t_images/' . $agenda->image);
            }

            $del = $agenda->delete();
            if ($del) {
                RedisHelper::deleteKeysAgenda();
                return $this->success("Agenda dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            // approved
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());

            // return response()->json([
            //     'code' => 500,
            //     'message' => "Internal Server Error!",
            //     "details" => $e->getMessage(),
            // ], 500);
        }
    }
}
