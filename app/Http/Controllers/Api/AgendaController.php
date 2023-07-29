<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Agenda\AgendaInterface as AgendaInterface;


class AgendaController extends Controller
{

    private $agendaRepository;

    public function __construct(AgendaInterface $agendaRepository)
    {
        $this->agendaRepository = $agendaRepository;
    }

    //M E T H O D E ======================
    // core
    public function index()
    {
        return $this->agendaRepository->getAllAgendas();
    }

    //findByCategoryId
    public function getByCategoryId($id)
    {
        return $this->agendaRepository->getAllAgendaByCategoryId($id);
    }

    // findByKeyword
    public function getAllByKeyword($keyword)
    {
        return $this->agendaRepository->getAllAgendaByKeyword($keyword);
    }
    
    //showBySlug
    public function findBySlug($slug)
    {
        return $this->agendaRepository->showBySlug($slug);
    }

    //findOne
    public function findById($id)
    {
        return $this->agendaRepository->findById($id);
    }

    // create
    public function add(Request $request)
    {
        return $this->agendaRepository->createAgenda($request);
    }

    // update
    public function edit(Request $request, $id)
    {

        //  return dd($request->all());
        return $this->agendaRepository->updateAgenda($request, $id);
    }

    // delete
    public function delete($id)
    {
        return $this->agendaRepository->deleteAgenda($id);
    }
}