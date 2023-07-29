<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Infrastruktur\InfrastrukturInterface;

class InfrastrukturController extends Controller
{
    private $infrastrukturRepository;

    public function __construct(InfrastrukturInterface $infrastrukturRepository)
    {
        $this->infrastrukturRepository = $infrastrukturRepository;
    }


    public function getAll()
    {

        return $this->infrastrukturRepository->getAll();
    }

    public function getById($id)
    {

        return $this->infrastrukturRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->infrastrukturRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->infrastrukturRepository->update($request, $id);
    }

    public function delete($id)
    {
        return $this->infrastrukturRepository->delete($id);
    }
}
