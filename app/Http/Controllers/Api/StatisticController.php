<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Statistic\StatisticInterface as StatisticInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Requests\StatisticRequest;
use App\Models\Statistic;
use App\Repositories\Statistic\StatisticRepository;

class StatisticController extends Controller
{
    
    private $statisticRepository;

    public function __construct(StatisticInterface $statisticRepository)
    {
        $this->statisticRepository = $statisticRepository;
    }

    //M E T H O D E ======================
    // core
    public function index()
    {
        return $this->statisticRepository->getAllStatistics();
    }

    //findOne
    public function findById($id)
    {
        return $this->statisticRepository->findById($id);
    }
    //byKeyword
    public function getAllByKeyword($keyword)
    {
        return $this->statisticRepository->getAllStatisticByKeyword($keyword);
    }

    // create
    public function add (Request $request)
    {
       return $this->statisticRepository->createStatistic($request);
    }

    // update
    public function edit(Request $request, $id)
    {
       return $this->statisticRepository->updateStatistic($request, $id);
    }

    // delete
    public function delete($id)
    {
        return $this->statisticRepository->deleteStatistic($id);
    }
}