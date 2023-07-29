<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Comment\CommentInterface;

class CommentController extends Controller
{
    private $commentRepository;

    public function __construct(CommentInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }


    public function getAll()
    {

        return $this->commentRepository->getAll();
    }

    public function getById($id)
    {

        return $this->commentRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->commentRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->commentRepository->update($request, $id);
    }

    public function delete($id)
    {
        return $this->commentRepository->delete($id);
    }
}
