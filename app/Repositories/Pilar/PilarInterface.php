<?php

namespace App\Repositories\Pilar;

use Illuminate\Http\Request;

interface PilarInterface {
    // getAll
    public function getAllPilars();
    //includesApplication
    public function getAllPilarsIncludeApp();
    // findOne
    public function findById($id);
    // insertData
    public function createPilar($request);
    // update
    public function updatePilar($request, $id);
    // delete
    public function deletePilar($id);
}