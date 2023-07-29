<?php

namespace App\Repositories\Regulasi;

use App\Helpers\Helper;
use App\Models\Regulasi;
use App\Repositories\Regulasi\RegulasiInterface;
use App\Traits\API_response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class RegulasiRepository implements RegulasiInterface
{
    private $regulasi;
    // 1 Day redis expired
    private $expired = 86400;
    private $destinationFiles = "files";
    use API_response;

    public function __construct(Regulasi $regulasi)
    {
        $this->regulasi = $regulasi;
    }


    public function getAll()
    {
        try {
            $keyOne = "regulasi-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Regulasi from (CACHE)", $result);
            }
            $data = Regulasi::latest('created_at')->paginate(10);
            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data Regulasi", $data);

            // $data = Regulasi::latest('created_at')->paginate(10);

            // return $this->success(
            //     " List semua data Regulasi",
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
            $keyOne = "regulasi-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Regulasi dengan ID = ($id) from (CACHE)", $result);
            }

            $data = Regulasi::find($id);
            if (!empty($data)) {
                Redis::set($keyOne, json_encode($data));
                Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
                return $this->success("Regulasi Dengan ID = ($id)", $data);
            }
            return $this->error("Not Found", "Regulasi dengan ID = ($id) tidak ditemukan!", 404);

            // $data = Regulasi::find($id);

            // // Check the data
            // if (!$data) return $this->error("Regulasi dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail Regulasi", $data);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'files'           => 'mimes:jpeg,png,jpg,gif,svg,png,docx,doc,pdf,pptx|max:5000'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            $file = $request->file('files');
            $fileName = $request->hasFile('files') ? time() . "." . $file->getClientOriginalExtension() : "";

            $data = [
                'title' => $request->title,
                'caption' => $request->caption,
                'link' => $request->link,
                'files' => $fileName,
                'created_by' => Auth::user()->id
            ];
            // Create Regulasi
            $add = Regulasi::create($data);

            if ($add) {
                // Storage::disk(['public' => 'regulasi'])->put($fileName, file_get_contents($request->files));
                // Save Image in Storage folder regulasi
                if ($request->hasFile('files')) {
                    $files = $request->file('files');
                    $files->storeAs($this->destinationFiles, $fileName, ['disk' => 'public']);
                }
                Helper::deleteRedis("regulasi-*");
                return $this->success("Regulasi Berhasil ditambahkan!", $data);
            }
            return $this->error("Failed", "Regulasi gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'files'           => 'mimes:jpeg,png,jpg,gif,svg,png,docx,doc,pdf,pptx|max:5000'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }
        try {
            // search
            $datas = Regulasi::find($id);
            // check
            if (!$datas) {
                return $this->error("Not Found", "Regulasi dengan ID = ($id) tidak ditemukan!", 404);
            }
            $datas['title'] = $request->title;
            $datas['caption'] = $request->caption;
            $datas['link'] = $request->link;
            $datas['updated_by'] = Auth::user()->id;

            if ($request->hasFile('files')) {
                // public storage
                $storage = Storage::disk('public');

                // Old iamge delete
                if ($storage->exists($this->destinationFiles . "/" . $datas->files)) {
                    $storage->delete($this->destinationFiles . "/" . $datas->files);
                }
                // Image name
                $file = $request->file('files');
                $fileName = time() . "." . $file->getClientOriginalExtension();

                $datas['files'] = $fileName;

                // Image save in public folder
                $files = $request->file('files');
                $files->storeAs($this->destinationFiles, $fileName, ['disk' => 'public']);
                Helper::resizeImage($files, $fileName, $request);
            } else {
                $datas['files'] = $datas->files;
            }

            // update datas
            if ($datas->save()) {
                Helper::deleteRedis("regulasi-*");
                return $this->success("Regulasi Berhasil diperbaharui!", $datas);
            }
            return $this->error("FAILED", "Regulasi Gagal diperbaharui!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = Regulasi::find($id);
            if (!$data) {
                return $this->error("Not Found", "Regulasi dengan ID = ($id) tidak ditemukan!", 404);
            }
            $storage = Storage::disk('public');
            if ($storage->exists($this->destinationFiles . "/" . $data->files)) {
                $storage->delete($this->destinationFiles . "/" . $data->files);
            }
            // approved
            if ($data->delete()) {
                Helper::deleteRedis("regulasi-*");
                return $this->success("Regulasi dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "Regulasi dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
