<?php

namespace App\Repositories\Application;

use Illuminate\Http\Request;

interface ApplicationInterface {
    // getAll
    public function getAllApplications();
    // findOne
    public function findById($id);
    // insertData
    public function createApplication($request);
    // update
    public function updateApplication($request, $id);
    // delete
    public function deleteApplication($id);
}