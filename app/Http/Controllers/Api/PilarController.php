<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Pilar\PilarInterface as PilarInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Requests\PilarRequest;
use App\Models\Pilar;
use App\Repositories\Pilar\PilarRepository;

class PilarController extends Controller
{
    
    private $pilarRepository;

    public function __construct(PilarInterface $pilarRepository)
    {
        $this->pilarRepository = $pilarRepository;
    }

    //M E T H O D E ======================
    // core
    public function index()
    {
        return $this->pilarRepository->getAllPilars();
    }

    //findOne
    public function findById($id)
    {
        return $this->pilarRepository->findById($id);
    }

    // create
    public function add (Request $request)
    {
       return $this->pilarRepository->createPilar($request);
    }

    // update
    public function edit(Request $request, $id)
    {
       return $this->pilarRepository->updatePilar($request, $id);
    }

    // delete
    public function delete($id)
    {
        return $this->pilarRepository->deletePilar($id);
    }

    public function includeApplications()
    {
        return $this->pilarRepository->getAllPilarsIncludeApp();
    }

}