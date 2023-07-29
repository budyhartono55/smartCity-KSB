<?php

namespace App\Repositories\Service;
use App\Http\Requests\ServiceRequest;

use Illuminate\Http\Request;

interface ServiceInterface {
    // getAll
    public function getAllServices();
    //byKeyword
    public function getAllServiceByKeyword($keyword);
    // findOne
    public function findById($id);
    // insertData
    public function createService($request);
    // update
    public function updateService($request, $id);
    // delete
    public function deleteService($id);
}