<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\News\NewsInterface;

class NewsController extends Controller
{
    private $newsRepository;

    public function __construct(NewsInterface $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }


    public function getAll()
    {

        return $this->newsRepository->getAll();
    }

    public function getById($id)
    {

        return $this->newsRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->newsRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->newsRepository->update($request, $id);
    }

    public function delete($id)
    {
        return $this->newsRepository->delete($id);
    }

    public function getByCategory($id)
    {
        return $this->newsRepository->getByCategory($id);
    }

    public function getAllBy($kondisi)
    {
        return $this->newsRepository->getAllBy($kondisi);
    }

    public function search($keyword)
    {
        return $this->newsRepository->search($keyword);
    }

    public function read($slug)
    {
        return $this->newsRepository->read($slug);
    }
}
