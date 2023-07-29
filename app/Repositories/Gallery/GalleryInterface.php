<?php

namespace App\Repositories\Gallery;

use Illuminate\Http\Request;

interface GalleryInterface {
    // getAll
    public function getAllGalleries();
    // findOne
    public function findById($id);
    // insertData
    public function createGallery($request);
    // update
    public function updateGallery($request, $id);
    // delete
    public function deleteGallery($id);
}