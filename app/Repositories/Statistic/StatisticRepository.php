<?php

namespace App\Repositories\Statistic;

use App\Repositories\Statistic\StatisticInterface as StatisticInterface;
use App\Models\Statistic;
use App\Http\Resources\StatisticResource;
use Exception;
use Illuminate\Http\Request;
use App\Traits\API_response;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StatisticRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Helpers\RedisHelper;
use App\Helpers\Helper;
use Intervention\Image\Facades\Image;

class StatisticRepository implements StatisticInterface
{

    protected $statistic;

    // Response API HANDLER
    use API_response;

    public function __construct(Statistic $statistic)
    {
        $this->statistic = $statistic;
    }

    // getAll
    public function getAllStatistics()
    {
        try {

            $key = 'AllStatistics_' . request()->get("page", 1);
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Statistic Kecamatan from (CACHE)", $result);
            }

            $statistic = Statistic::latest('created_at')->paginate(12);
            $statistic->getCollection()->transform(function ($item) {
                //rubah format kiriman ke tipe nomor supaya enak ditampilkan di FE
                $item->luas_wilayah = number_format($item->luas_wilayah, 0, ',', '.');
                $item->jumlah_penduduk = number_format($item->jumlah_penduduk, 0, ',', '.');

                //decode juga koordinatnya sebelum kirim FE
                $koordinat = json_decode($item->koordinat, true);
                $item->koordinat = $koordinat;
                return $item;
            });
            if ($statistic) {
                Redis::set($key, json_encode($statistic));
                Redis::expire($key, 60); // Cache for 60 seconds

                return $this->success("List keseluruhan Statistic Kecamatan", $statistic);
            }

            //==================
            // No-REDIS
            // $statistic = Statistic::paginate(3);
            // return $this->success("List keseluruhan Statistic Kecamatan", $statistic);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    public function getAllStatisticByKeyword($keyword)
    {
        try {
            if (Redis::exists('statistic_' . $keyword)) {
                $result = json_decode(Redis::get('statistic_' . $keyword));
                return $this->success("List Statistic Kecamatan dengan keyword = ($keyword) from (CACHE)", $result);
            }

            // If not, perform the query and cache the result in Redis
            $result = Statistic::where('nama_kecamatan', 'LIKE', '%' . $keyword . '%')
                ->latest('created_at')
                ->paginate(12);
            $result->getCollection()->transform(function ($item) {
                //rubah format kiriman ke tipe nomor supaya enak ditampilkan di FE
                $item->luas_wilayah = number_format($item->luas_wilayah, 0, ',', '.');
                $item->jumlah_penduduk = number_format($item->jumlah_penduduk, 0, ',', '.');

                //decode juga koordinatnya sebelum kirim FE
                $koordinat = json_decode($item->koordinat, true);
                $item->koordinat = $koordinat;
                return $item;
            });
            if (count($result)) {
                Redis::set('statistic_' . $keyword, json_encode($result));
                Redis::expire('statistic_' . $keyword, 60); // Cache for 1 minute
                return $this->success("List Statistic Kecamatan dengan keyword = ($keyword)", $result);
            } else {
                return $this->error("Not Found", "Statistic Kecamatan dengan keyword = ($keyword) tidak ditemukan!", 404);
            }

            //==================
            //NO-REDIS
            // $result = Statistic::where('statistic_title', 'LIKE', '%' . $keyword . '%')->get();
            // if (count($result)) {
            //     //  return Response()->json($result);
            //     return $this->success("Detail Statistic Kecamatan", $result);
            // } else {
            //     return $this->error("Statistic Kecamatan dengan keyword = ($keyword) tidak ditemukan!", 404);
            // }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('statistic_' . $id)) {
                $result = json_decode(Redis::get('statistic_' . $id));
                return $this->success("Detail Statistic Kecamatan dengan ID = ($id) from (CACHE)", $result);
            }

            $statistic = Statistic::find($id);
            //rubah format kiriman ke tipe nomor supaya enak ditampilkan di FE
            $statistic->luas_wilayah = number_format($statistic->luas_wilayah, 0, ',', '.');
            $statistic->jumlah_penduduk = number_format($statistic->jumlah_penduduk, 0, ',', '.');

            //decode juga koordinatnya sebelum kirim FE
            $koordinat = json_decode($statistic->koordinat, true);
            $statistic->koordinat = $koordinat;

            if ($statistic) {
                Redis::set('statistic_' . $id, json_encode($statistic));
                Redis::expire('statistic_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Statistic Kecamatan", $statistic);
            } else {
                return $this->error("Not Found", "Statistic Kecamatan dengan ID = ($id) tidak ditemukan!", 404);
            }

            //===================
            // NO-REDIS
            // Check the user
            // $statistic = Statistic::find($id);
            // if(!$statistic) return $this->error("Statistic Kecamatan dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail statistic", $statistic);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createStatistic($request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nama_kecamatan' =>  'required',
                'icon'          =>  'image|
                                    mimes:jpeg,png,jpg,gif,svg|
                                    max:2048',
            ],
            [
                'nama_kecamatan.required' => 'Mohon isikan statistic_title',
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
            $statistic = new Statistic();
            $statistic->nama_kecamatan = $request->nama_kecamatan;

            // $statistic->luas_wilayah = $request->luas_wilayah;
            $luasWilayah = str_replace('.', ',', $request->luas_wilayah);
            $luasWilayah = (float) str_replace(',', '', $luasWilayah);
            if (is_int($luasWilayah)) {
                $luasWilayah = $luasWilayah . '.0';
            }
            $statistic->luas_wilayah = $luasWilayah;

            // $statistic->jumlah_penduduk = $request->jumlah_penduduk;
            $jumlahPenduduk = str_replace('.', ',', $request->jumlah_penduduk);
            $jumlahPenduduk = (float) str_replace(',', '', $jumlahPenduduk);
            if (is_int($jumlahPenduduk)) {
                $jumlahPenduduk = $jumlahPenduduk . '.0';
            }
            $statistic->jumlah_penduduk = $jumlahPenduduk;

            $koordinat = $request->only(['latitude', 'longitude']);

            // kirim format dalam bentuk json ke DB
            // nanti decode di pengambilan.
            $statistic->koordinat = json_encode($koordinat);
            // dd($koordinat);

            if ($request->hasFile('icon')) {
                $destination = 'public/icons';
                $t_destination = 'public/thumbnails/t_icons';
                $icon = $request->file('icon');
                $iconName = time() . "." . $icon->getClientOriginalExtension();

                $statistic->icon = $iconName;
                //storeOriginal
                $icon->storeAs($destination, $iconName);

                // compress to thumbnail 
                Helper::resizeIcon($icon, $iconName, $request);
            }

            $create = $statistic->save();
            if ($create) {
                RedisHelper::deleteKeysStatistic();
                return $this->success("Statistic Kecamatan Berhasil ditambahkan!", $statistic);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }

    // update
    public function updateStatistic($request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nama_kecamatan' => 'required',
                'icon'           => 'image|
                                    mimes:jpeg,png,jpg,gif,svg|
                                    max:2048',
            ],
            [
                'nama_kecamatan.required' => 'Mohon isikan nama_kecamatan',
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
            $statistic = Statistic::find($id);

            // checkID
            if (!$statistic) {
                return $this->error("Not Found", "Statistic Kecamatan dengan ID = ($id) tidak ditemukan!", 404);
            }
            if ($request->hasFile('icon')) {
                //checkImage
                if ($statistic->icon) {
                    Storage::delete('public/icons/' . $statistic->icon);
                    Storage::delete('public/thumbnails/t_icons/' . $statistic->icon);
                }
                $destination = 'public/icons';
                $t_destination = 'public/thumbnails/t_icons';
                $icon = $request->file('icon');
                $iconName = time() . "." . $icon->getClientOriginalExtension();
                // dd($iconName);

                $statistic->icon = $iconName;
                //storeOriginal
                $icon->storeAs($destination, $iconName);

                // compress to thumbnail 
                Helper::resizeIcon($icon, $iconName, $request);
            } else {
                if ($request->delete_image) {
                    Storage::delete('public/icons/' . $statistic->icon);
                    Storage::delete('public/thumbnails/t_icons/' . $statistic->icon);
                    $statistic->icon = null;
                }
                $statistic->icon = $statistic->icon;
            }

            //approved
            $statistic['nama_kecamatan'] = $request->nama_kecamatan;
            $luasWilayah = str_replace('.', ',', $request->luas_wilayah);
            $luasWilayah = (float) str_replace(',', '', $luasWilayah);
            if (is_int($luasWilayah)) {
                $luasWilayah = $luasWilayah . '.0';
            }
            $statistic['luas_wilayah'] = $luasWilayah;

            $jumlahPenduduk = str_replace('.', ',', $request->jumlah_penduduk);
            $jumlahPenduduk = (float) str_replace(',', '', $jumlahPenduduk);
            if (is_int($jumlahPenduduk)) {
                $jumlahPenduduk = $jumlahPenduduk . '.0';
            }
            $statistic['jumlah_penduduk'] = $jumlahPenduduk;

            $koordinat = $request->only(['latitude', 'longitude']);

            // kirim format dalam bentuk json 
            // nanti ngambilnya di FE bisa di decode.
            $statistic->koordinat = json_encode($koordinat);

            // approved
            // $statistic['statistic_title'] = $request->statistic_title;
            // $statistic['url'] = $request->url;
            // $statistic['description'] = $request->description;
            // $statistic['slug'] =  Str::slug($request->statistic_title, '-');
            // $user = Auth::user();
            // $statistic['created_by'] = $user->id;

            //save
            $update = $statistic->save();
            if ($update) {
                RedisHelper::deleteKeysStatistic();
                return $this->success("Statistic Kecamatan Berhasil diperbaharui!", $statistic);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 499);
        }
    }


    // delete
    public function deleteStatistic($id)
    {

        try {
            // search
            $statistic = Statistic::find($id);
            if (!$statistic) {
                return $this->error("Not Found", "Statistic Kecamatan dengan ID = ($id) tidak ditemukan!", 404);
            }
            if ($statistic->icon) {
                Storage::delete('public/icons/' . $statistic->icon);
                Storage::delete('public/thumbnails/t_icons/' . $statistic->icon);
            }
            // approved
            $del = $statistic->delete();
            if ($del) {
                RedisHelper::deleteKeysStatistic();
                return $this->success("Statistic Kecamatan dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }
}
