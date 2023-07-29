<?php

namespace App\Repositories\News;

use App\Helpers\Helper;
use App\Models\News;
use App\Repositories\News\NewsInterface;
use App\Traits\API_response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class NewsRepository implements NewsInterface
{
    private $news;
    // 1 Minute redis expired
    private $expired = 360;
    private $destinationImage = "images";
    private $destinationImageThumbnail = "thumbnails/t_images";
    private $hiddenField = ['id', 'user_id', 'created_by', 'updated_by'];

    use API_response;

    public function __construct(News $news)
    {
        $this->news = $news;
    }

    public function getAll()
    {
        try {
            if (!Auth::check() or Helper::isAdmin()) {
                // Key 1
                $keyAll = 'news-getAll' . self::checkLogin() . request()->get('page', 1);
                if (Redis::exists($keyAll)) {
                    $result = json_decode(Redis::get($keyAll));
                    return $this->success("List Berita from (CACHE)", $result);
                }
                $berita = $this->query()->paginate(12);
                self::hiddenField($berita);

                Redis::set($keyAll, json_encode($berita));
                Redis::expire($keyAll,  $this->expired); // Cache for 60 seconds


                return $this->success("List kesuluruhan Berita", $berita);
            } else {
                //key2
                $user_id = Auth::id();
                $keyOne = 'news-getAllByUser-' . $user_id . request()->get('page', 1);

                if (Redis::exists($keyOne)) {
                    $result = json_decode(Redis::get($keyOne));
                    return $this->success("List Berita berdasarkan user_id = ($user_id) from (CACHE)", $result);
                }

                $berita = $this->query()->where('news.user_id', auth()->user()->id)->paginate(12);
                Redis::set($keyOne, json_encode($berita));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                return $this->success("List Berita berdasarkan user_id = ($user_id)", $berita);
            }

            // NO REDIS
            // $hidden = ['id', 'category_id', 'user_id'];

            // if (!Auth::check() or (auth()->user()->level == 1)) {
            //     $berita = $this->query()->paginate(10);
            //     // $hidden = ['category_id', 'user_id'];
            // } else {
            //     $berita = $this->query()->where('news.user_id', auth()->user()->id)->paginate(10);
            // }

            // $berita->makeHidden($hidden);


            // return $this->success(
            //     " List semua data Berita",
            //     $berita
            // );
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function getAllBy($kondisi)
    {

        try {
            $search = $kondisi == "top" ? 'views'  : 'posted_at';
            // $hidden = ['id', 'category_id', 'user_id'];

            if (!Auth::check() or Helper::isAdmin()) {

                $keyAll = "news-getAllBy" . self::checkLogin() . Str::slug($kondisi) . request()->get('page', 1);
                if (Redis::exists($keyAll)) {
                    $result = json_decode(Redis::get($keyAll));
                    return $this->success("List Berita berdasarkan populer from (CACHE)", $result);
                }

                $berita = $this->query($search)->paginate(12);
                self::hiddenField($berita);
                Redis::set($keyAll, json_encode($berita));
                Redis::expire($keyAll,  $this->expired); // Cache for 60 seconds
                return $this->success("List kesuluruhan Berita", $berita);

                // $hidden = ['category_id', 'user_id'];
            } else {
                //key2
                $keyOne = "news-getAllBy-" . auth()->user()->id . "-" . Str::slug($kondisi) .  request()->get('page', 1);
                if (Redis::exists($keyOne)) {
                    $result = json_decode(Redis::get($keyOne));
                    return $this->success("List Berita berdasarkan Populer from (CACHE)", $result);
                }
                $berita = $this->query($search)->where('news.user_id', auth()->user()->id)->paginate(12);
                Redis::set($keyOne, json_encode($berita));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                return $this->success("List Berita berdasarkan Populer", $berita);
            }
            // $berita->makeHidden($hidden);
            // $search = $kondisi == "top" ? 'views'  : 'posted_at';
            // // $hidden = ['id', 'category_id', 'user_id'];

            // if (!Auth::check() or (auth()->user()->level == 1)) {
            //     $berita = $this->query($search)->paginate(10);

            //     // $hidden = ['category_id', 'user_id'];
            // } else {
            //     $berita = $this->query($search)->where('news.user_id', auth()->user()->id)->paginate(10);
            // }
            // // $berita->makeHidden($hidden);

            // return $this->success(
            //     " List filter semua data Berita",
            //     $berita
            // );
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }



    // findOne
    public function getById($id)
    {
        try {

            $keyOne = "news-getById" . self::checkLogin() . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Berita dengan ID = ($id)  from (CACHE)", $result);
            }
            $cek = News::find($id);
            if ($cek) {
                $berita = $this->query()->where('news.id', $id)->get();
                self::hiddenField($berita);
                Redis::set($keyOne, json_encode($berita));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                return $this->success("Berita dengan ID = ($id)", $berita);
            }
            return $this->error("Not Found", "Berita dengan ID = ($id) tidak ditemukan!", 404);
            // $data = $this->query()->where('news.id', $id)->get();
            // return $this->success("Detail Berita", $data);
            // $hidden = ['id', 'category_id', 'user_id'];

            // if (Auth::check()) {
            //     $hidden = ['category_id', 'user_id'];
            // }
            // $data->makeHidden($hidden);

        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'berita_title'     => 'required',
            'description'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:3000',
            'category_id'  => 'required',
            'posted_at' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            $fileName = $request->hasFile('image') ? time() . "." . $request->image->getClientOriginalExtension() : "";

            $data = [
                'berita_title' => $request->berita_title,
                'description' => $request->description,
                'image' => $fileName,
                'slug' => Str::slug($request->berita_title),
                'category_id' => $request->category_id,
                'user_id' => Auth::user()->id,
                'created_by' => Auth::user()->id,
                'posted_at' => Carbon::createFromFormat('d-m-Y', $request->posted_at)

            ];
            // Create Berita
            $add = News::create($data);

            if ($add) {
                // Storage::disk(['public' => 'news'])->put($fileName, file_get_contents($request->image));
                // Save Image in Storage folder news
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                    Helper::resizeImage($image, $fileName, $request);
                }
                // delete Redis when insert data
                Helper::deleteRedis("news-*");
                return $this->success("Berita Berhasil ditambahkan!", $data);
            }

            return $this->error("FAILED", "Berita gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'berita_title'     => 'required',
            'description'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:2000',
            'category_id'  => 'required',
            'posted_at'  => 'required',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
            // return response()->json($validator->errors(), 422);
        }
        try {
            // search
            $datas = News::find($id);
            // check
            if (!$datas) {
                return $this->error("Not Found", "Berita dengan ID = ($id) tidak ditemukan!", 404);
            }
            $datas['berita_title'] = $request->berita_title;
            $datas['description'] = $request->description;
            $datas['slug'] = Str::slug($request->berita_title);
            $datas['views'] = $datas->views;
            $datas['category_id'] = $request->category_id;
            $datas['user_id'] = Auth::user()->id;
            $datas['updated_by'] = Auth::user()->id;
            $datas['posted_at'] = Carbon::createFromFormat('d-m-Y', $request->posted_at);

            $storage = Storage::disk('public');
            if ($request->hasFile('image')) {
                // public storage


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
                // delete Redis when insert data
                Helper::deleteRedis("news-*");

                return $this->success("Berita Berhasil diperbaharui!", $datas);
            }
            return $this->error("FAILED", "Berita gagal diperbaharui!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = News::find($id);
            if (empty($data)) {
                return $this->error("Not Found", "Berita dengan ID = ($id) tidak ditemukan!", 404);
            }
            Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $data->image);

            // approved
            if ($data->delete()) {
                Helper::deleteRedis("news-*");
                return $this->success("Berita dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }

            return $this->error("FAILED", "Berita dengan ID = ($id) gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function getByCategory($id)
    {
        try {
            $keyOne = "news-getByCategory-" . self::checkLogin() . Str::slug($id) . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Berita dengan Kategory = ($id)  from (CACHE)", $result);
            }
            $berita = $this->query()->where(['category_id' => $id])->paginate(12);

            if (!empty($berita)) {
                self::hiddenField($berita);
                Redis::set($keyOne, json_encode($berita));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                return $this->success("Berita By Category = ($id)", $berita);
            }
            return $this->error("Not Found", "Berita dengan Category = ($id) tidak ditemukan!", 404);
            //code...
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }


        // $berita = $this->query()->where(['category_id' => $id])->paginate(10);

        // if (empty($berita)) {
        //     return $this->error("Category dengan ID = ($id) tidak ditemukan!", 404);
        // }

        // $hidden = ['id', 'category_id', 'user_id'];
        // if (Auth::check()) {
        //     $hidden = ['category_id', 'user_id'];
        // }
        // $berita->makeHidden($hidden);

        // return $this->success("Berita Detail By Category", $berita);
    }

    public function search($keyword)
    {
        try {
            $keyOne = "news-search-" . self::checkLogin() . Str::slug($keyword) .  request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Berita dengan Keyword = ($keyword)  from (CACHE)", $result);
            }
            $berita = $this->query()->where('berita_title', 'LIKE', '%' . $keyword . '%')->paginate(12);
            if (!empty($berita)) {
                self::hiddenField($berita);
                Redis::set($keyOne, json_encode($berita));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                $berita['keyword'] = $keyword;
                return $this->success("Berita By Keyword = ($keyword)", $berita);
            }
            return $this->error("Not Found", "Berita dengan keyword = ($keyword) tidak ditemukan!", 404);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
        // $berita = $this->query()->where('berita_title', 'LIKE', '%' . $keyword . '%')->get();
        // // return $berita;
        // if (empty($berita)) {
        //     return $this->error("News dengan Keyword = ($keyword) tidak ditemukan!", 404);
        // }
        // $hidden = ['id', 'category_id', 'user_id'];
        // if (Auth::check()) {
        //     $hidden = ['category_id', 'user_id'];
        // }
        // $berita->makeHidden($hidden);

        // $berita['keyword'] = $keyword;

        // return $this->success("Search Berita", $berita);
    }

    public function read($slug)
    {
        try {
            $keyOne = "news-read-" . self::checkLogin() . Str::slug($slug);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Berita dengan slug = ($slug)  from (CACHE)", $result);
            }
            $berita = $this->query()->where('slug', $slug)->first();
            if (!empty($berita)) {
                self::hiddenField($berita);
                Redis::set($keyOne, json_encode($berita));
                Redis::expire($keyOne,  $this->expired); // Cache for 60 seconds
                $this->addViews($berita->id);
                Helper::deleteRedis("news-*");
                return $this->success("Berita dengan Slug = ($slug)", $berita);
            }
            return $this->error("Not Found", "Berita dengan Slug = ($slug) tidak ditemukan!", 404);
            //code...
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
        // $berita = $this->query()->where('slug', $slug)->first();
        // if (!empty($berita)) {

        //     $this->addViews($berita->id);
        //     // $hidden = ['id', 'category_id', 'user_id'];
        //     // if (Auth::check()) {
        //     //     $hidden = ['category_id', 'user_id'];
        //     // }
        //     // $berita->makeHidden($hidden);
        // }

        // return $this->success("Read Berita", $berita);
    }

    function addViews($id_berita)
    {
        $datas = News::find($id_berita);
        $datas['views'] = $datas->views + 1;
        return $datas->save();
    }

    function query($kondisi = "posted_at")
    {
        return News::join('categories', 'categories.id', '=', 'news.category_id')
            ->join('users', 'users.id', '=', 'news.user_id')
            ->latest($kondisi == "views" ? 'views' : 'posted_at')
            ->select(['news.*', 'categories.category_title', 'users.name AS author']);
    }

    function hiddenField($berita)
    {
        if (!Auth::check()) {
            $berita->makeHidden($this->hiddenField);
        }
    }

    function checkLogin()
    {
        return !Auth::check() ? "-public-" : "-admin-";
    }
}
