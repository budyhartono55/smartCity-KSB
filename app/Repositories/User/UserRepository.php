<?php

namespace App\Repositories\User;

use App\Helpers\Helper;
use App\Models\User;
use App\Repositories\User\UserInterface;
use App\Traits\API_response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserRepository implements UserInterface
{
    // 1 Day redis expired
    private $expired = 60;
    private $destinationImage = "images";
    private $destinationImageThumbnail = "thumbnails/t_images";
    private $User;
    use API_response;

    public function __construct(User $User)
    {
        $this->User = $User;
    }


    public function getAll()
    {
        try {

            $keyOne = "user-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data user from (CACHE)", $result);
            }
            $data = User::latest()->paginate(12);

            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data User", $data);

            // $data = User::all();
            // return $this->success(
            //     " List semua data User",
            //     $data
            // );
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function getAllTrash()
    {
        try {

            $keyOne = "user-getAllTrash" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Trash user  from (CACHE)", $result);
            }
            $data = User::onlyTrashed()->latest()->paginate(12);
            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data Trash User", $data);

            // $data = User::all();
            // return $this->success(
            //     " List semua data User",
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
            $keyOne = "user-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("User dengan ID = ($id) from (CACHE)", $result);
            }

            $data = User::find($id);
            if (!empty($data)) {
                Redis::set($keyOne, json_encode($data));
                Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
                return $this->success("User Dengan ID = ($id)", $data);
            }
            return $this->error("Not Found", "User dengan ID = ($id) tidak ditemukan!", 404);

            // $data = User::find($id);

            // // Check the user
            // if (!$data) return $this->error("User dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail User", $data);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'username'     => 'required|unique:users',
            'email'     => 'required|email',
            'password'           => 'required',
            'confirm_password' => 'required|same:password',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:500',
            'level'           => 'required',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            $fileName = $request->hasFile('image') ? time() . "." . $request->image->getClientOriginalExtension() : "";

            $data = [
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'image' => $fileName,
                'password' => bcrypt($request->password),
                'address' => $request->address,
                'contact' => $request->contact,
                'level' => $request->level,
                'created_by' => Auth::user()->id

            ];
            // Create User
            $add = User::create($data);

            if ($add) {
                // Storage::disk(['public' => 'User'])->put($fileName, file_get_contents($request->image));
                // Save Image in Storage folder User
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                    Helper::resizeImage($image, $fileName, $request);
                }
                Helper::deleteRedis("user-*");
                return $this->success("User Berhasil ditambahkan!", $data);
            }
            return $this->error("FAILED", "User Gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'username'     => 'required',
            'email'     => 'required|email',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:2000',
            'level'           => 'required',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }
        try {
            // search
            $datas = User::find($id);
            // check
            if (!$datas) {
                return $this->error("Not Found", "User dengan ID = ($id) tak diditemukan!", 404);
            }

            $fileName = $request->hasFile('image') ? time() . "." . $request->image->getClientOriginalExtension() : "";

            $datas['name'] = $request->name;
            $datas['username'] = $request->username;
            $datas['email'] = $request->email;
            $datas['address'] = $request->address;
            $datas['contact'] = $request->contact;
            $datas['level'] = $request->level;
            $datas['updated_by'] = Auth::user()->id;
            // public storage

            if ($request->hasFile('image')) {
                // Old image delete
                Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $datas->image);
                $datas->image = $fileName;

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
                Helper::deleteRedis("user-*");
                return $this->success("User Berhasil diperbaharui!", $datas);
            }

            return $this->error("FAILED", "User Gagal diperbaharui!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
    public function deleteSementara($id)
    {
        try {

            // search
            $data = User::find($id);
            if (!$data) {
                return $this->error("Not Found", "User dengan ID = ($id) tidak ditemukan!", 404);
            }

            if ($data->delete()) {
                Helper::deleteRedis("user-*");
                return $this->success("User dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "User dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
    public function deletePermanent($id)
    {
        try {

            $data = User::onlyTrashed()->find($id);
            if (!$data) {
                return $this->error("Not Found", "User dengan ID = ($id) tidak ditemukan!", 404);
            }
            // Old image delete
            Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $data->image);
                // approved
            ;
            if ($data->forceDelete()) {
                Helper::deleteRedis("user-*");
                return $this->success("User dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "User dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function restore()
    {
        try {
            $data = User::onlyTrashed();
            if ($data->restore()) {
                Helper::deleteRedis("user-*");
                return $this->success("Restore User Berhasil!", "COMPLETED");
            }
            return $this->error("FAILED", "Restore User Gagal!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function restoreById($id)
    {
        try {
            $data = User::onlyTrashed()->where('id', $id);
            if ($data->restore()) {
                Helper::deleteRedis("user-*");
                return $this->success("Restore User dengan ID = ($id) Berhasil!", "COMPLETED");
            }
            return $this->error("FAILED", "Restore User dengan ID = ($id) Gagal!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function changePassword($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_password'           => 'required',
            'old_password'           => 'required',
            'confirm_password' => 'required|same:old_password',

        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }
        try {

            // search
            $datas = User::find($id);
            if (!$datas) {
                return $this->error("Not Found", "User dengan ID = ($id) tidak ditemukan!", 404);
            }

            if (Hash::check($request->old_password, $datas->password)) {

                $datas['password'] = bcrypt($request->new_password);
                $datas['updated_by'] = Auth::user()->id;

                // update datas
                if ($datas->save()) {
                    Helper::deleteRedis("user-*");
                    return $this->success("Password Berhasil diperbaharui!", $datas);
                }

                return $this->error("FAILED", "Password Gagal diperbaharui!", 400);
            }
            return $this->error("FAILED", "Password Lama Salah", 422);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function resetPassword($id)
    {

        try {

            // search
            $datas = User::find($id);
            if (!$datas) {
                return $this->error("Not Found", "User dengan ID = ($id) tidak ditemukan!", 404);
            }

            $datas['password'] = bcrypt($datas->username);
            $datas['updated_by'] = Auth::user()->id;

            // update datas
            if ($datas->save()) {
                Helper::deleteRedis("user-*");
                return $this->success("Password Berhasil direset!", $datas);
            }

            return $this->error("FAILED", "Password Gagal direset!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
