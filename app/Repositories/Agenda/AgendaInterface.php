<?php

namespace App\Repositories\Agenda;

use Illuminate\Http\Request;

interface AgendaInterface {
    // getAll
    public function getAllAgendas();
    // getAll Agenda By Category
    public function getAllAgendaByCategoryId($id);
    // getAll Agenda By Keyword
    public function getAllAgendaByKeyword($keyword);
    //readBySlug 
    public function showBySlug($slug);
    // findOne
    public function findById($id);
    // insertData
    public function createAgenda($request);
    // update
    public function updateAgenda($request, $id);
    // delete
    public function deleteAgenda($id);
}