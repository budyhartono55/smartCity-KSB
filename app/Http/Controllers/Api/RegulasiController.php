<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Regulasi\RegulasiInterface;

class RegulasiController extends Controller
{
    private $regulasiRepository;

    public function __construct(RegulasiInterface $regulasiRepository)
    {
        $this->regulasiRepository = $regulasiRepository;
    }


    public function getAll()
    {

        return $this->regulasiRepository->getAll();
    }

    public function getById($id)
    {

        return $this->regulasiRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->regulasiRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->regulasiRepository->update($request, $id);
    }

    public function delete($id)
    {
        return $this->regulasiRepository->delete($id);
    }
}
