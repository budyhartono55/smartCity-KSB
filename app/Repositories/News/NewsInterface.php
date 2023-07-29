<?php

namespace App\Repositories\News;

interface NewsInterface
{
    public function getAll();
    public function getById($id);
    public function save($request);
    public function update($request, $id);
    public function delete($id);
    public function getByCategory($id);
    public function getAllBy($kondisi);
    public function search($keyboard);
    public function read($slug);
}
