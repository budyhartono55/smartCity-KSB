<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Application\ApplicationInterface as ApplicationInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Requests\ApplicationRequest;
use App\Models\Application;
use App\Repositories\Application\ApplicationRepository;

class ApplicationController extends Controller
{
    
    private $applicationRepository;

    public function __construct(ApplicationInterface $applicationRepository)
    {
        $this->applicationRepository = $applicationRepository;
    }

    //M E T H O D E ======================
    // core
    public function index()
    {
        return $this->applicationRepository->getAllApplications();
    }

    //findOne
    public function findById($id)
    {
        return $this->applicationRepository->findById($id);
    }

    // create
    public function add (Request $request)
    {
       return $this->applicationRepository->createApplication($request);
    }

    // update
    public function edit(Request $request, $id)
    {
       return $this->applicationRepository->updateApplication($request, $id);
    }

    // delete
    public function delete($id)
    {
        return $this->applicationRepository->deleteApplication($id);
    }
}