<?php

namespace App\Repositories\Contact;

use App\Helpers\Helper;
use App\Models\Contact;
use App\Repositories\Contact\ContactInterface;
use App\Traits\API_response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class ContactRepository implements ContactInterface
{
    private $contact;
    // 1 Day redis expired
    private $expired = 86400;
    private $destinationImage = "images";
    private $destinationImageThumbnail = "thumbnails/t_images";
    use API_response;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }


    public function getAll()
    {
        try {
            $keyOne = "contact-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Contact from (CACHE)", $result);
            }
            $data = Contact::latest('created_at')->paginate(10);
            Redis::set($keyOne, json_encode($data));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data Contact", $data);

            // $data = Contact::latest('created_at')->paginate(10);

            // return $this->success(
            //     " List semua data Contact",
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
            $keyOne = "contact-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Contact dengan ID = ($id) from (CACHE)", $result);
            }

            $data = Contact::find($id);
            if (!empty($data)) {
                Redis::set($keyOne, json_encode($data));
                Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
                return $this->success("Contact Dengan ID = ($id)", $data);
            }
            return $this->error("Not Found", "Contact dengan ID = ($id) tidak ditemukan!", 404);

            // $data = Contact::find($id);

            // // Check the data
            // if (!$data) return $this->error("Contact dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail Contact", $data);
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'contact'     => 'required',
            'caption'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:500'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            $fileName = $request->hasFile('image') ? time() . "." . $request->image->getClientOriginalExtension() : "";

            $data = [
                'contact' => $request->contact,
                'caption' => $request->caption,
                'image' => $fileName,
                'created_by' => Auth::user()->id
            ];
            // Create Contact
            $add = Contact::create($data);

            if ($add) {
                // Storage::disk(['public' => 'contact'])->put($fileName, file_get_contents($request->image));
                // Save Image in Storage folder contact
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $image->storeAs($this->destinationImage, $fileName, ['disk' => 'public']);
                    Helper::resizeImage($image, $fileName, $request);
                }
                Helper::deleteRedis("contact-*");
                return $this->success("Contact Berhasil ditambahkan!", $data);
            }
            return $this->error("FAILED", "Contact gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contact'     => 'required',
            'caption'     => 'required',
            'image'           => 'image|mimes:jpeg,png,jpg,gif,svg|max:500'
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }
        try {
            // search
            $datas = Contact::find($id);
            // check
            if (!$datas) {
                return $this->error("Not Found", "Contact dengan ID = ($id) tidak ditemukan!", 404);
            }
            $datas['contact'] = $request->contact;
            $datas['caption'] = $request->caption;
            $datas['updated_by'] = Auth::user()->id;

            if ($request->hasFile('image')) {
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
                Helper::deleteRedis("contact-*");
                return $this->success("Contact Berhasil diperbaharui!", $datas);
            }
            return $this->error("FAILED", "Contact Gagal diperbaharui!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = Contact::find($id);
            if (!$data) {
                return $this->error("Not Found", "Contact dengan ID = ($id) tidak ditemukan!", 404);
            }
            Helper::deleteImage($this->destinationImage, $this->destinationImageThumbnail, $data->image);

            // approved
            if ($data->delete()) {
                Helper::deleteRedis("contact-*");
                return $this->success("Contact dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
            return $this->error("FAILED", "Contact dengan ID = ($id) Gagal dihapus!", 400);
        } catch (Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
