<?php

namespace App\Repositories\Comment;

use App\Helpers\Helper;
use App\Models\Comment;
use App\Repositories\Comment\CommentInterface;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Traits\API_response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CommentRepository implements CommentInterface
{
    private $comment;
    // 1 minute redis expired
    private $expired = 60;
    use API_response;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }


    public function getAll()
    {
        try {
            $keyOne = "comment-getAll" . request()->get('page', 1);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("List Data Comment from (CACHE)", $result);
            }
            $comment = Comment::latest()->paginate(12);
            Redis::set($keyOne, json_encode($comment));
            Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
            return $this->success("List Data Comment", $comment);
            // $data = Comment::all();
            // return $this->success(
            //     " List semua data Comment",
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
            $keyOne = "comment-getById-" . Str::slug($id);
            if (Redis::exists($keyOne)) {
                $result = json_decode(Redis::get($keyOne));
                return $this->success("Comment By ID = ($id) from (CACHE)", $result);
            }

            $comment = Comment::find($id);
            if (!empty($comment)) {
                Redis::set($keyOne, json_encode($comment));
                Redis::expire($keyOne, $this->expired); // Cache for 60 seconds
                return $this->success("Comment By ID = ($id)", $comment);
            }
            return $this->error("Not Found", "Comment dengan ID = ($id) tidak ditemukan!", 404);
            // $data = Comment::find($id);

            // Check the user
            // if (!$data) return $this->error("Comment dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail Comment", $data);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function save($request)
    {
        $validator = Validator::make($request->all(), [
            'comment_context'     => 'required',
            'comment_id'  => 'required',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        $data = [
            'comment_context' => $request->comment_context,
            'user_id' => Auth::user()->id,
            'comment_id' => $request->comment_id,
            'created_by' => Auth::user()->id,
        ];
        try {
            $add = Comment::create($data);

            if ($add) {
                Helper::deleteRedis("comment-*");
                return $this->success("Comment Berhasil ditambahkan!", [$data]);
            }
            return $this->error("FAILED", "Comment Gagal ditambahkan!", 400);
        } catch (\Exception $e) {
            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage(), 500);
        }
    }

    public function update($request, $id)
    {

        $validator = Validator::make($request->all(), [
            'comment_context'     => 'required',
            'comment_id'  => 'required',
        ]);
        if ($validator->fails()) {
            return $this->error("Validator!", $validator->errors(), 422);
        }

        try {
            // search
            $datas = Comment::find($id);

            // check
            if (!$datas) {
                return $this->error("Comment dengan ID = ($id) tidak ditemukan!", 404);
            } else {
                // dd($request->web_title);
                $datas['comment_context'] = $request->comment_context;
                $datas['comment_id'] = $request->comment_id;
                $datas['user_id'] = Auth::user()->id;
                $datas['updated_by'] = Auth::user()->id;

                // $this->comment->update($data,$id);
                if ($datas->save()) {
                    Helper::deleteRedis("comment-*");
                    return $this->success("Comment Berhasil diubah!", [$datas]);
                }

                return $this->error("FAILED", "Comment Gagal diubah!", 400);
            }
        } catch (Exception $e) {

            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // search
            $data = Comment::find($id);
            if (!$data) {
                return $this->error("Not Found", "Comment dengan ID = ($id) tidak ditemukan!", 404);
            }
            // approved
            if ($data->delete()) {
                Helper::deleteRedis("comment-*");
                return $this->success("Comment dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }

            return $this->error("FAILED", "Comment dengan ID = ($id) gagal dihapus!", 400);
        } catch (Exception $e) {

            // return $this->error($e->getMessage(), $e->getCode());
            return $this->error("Internal Server Error!", $e->getMessage());
        }
    }
}
