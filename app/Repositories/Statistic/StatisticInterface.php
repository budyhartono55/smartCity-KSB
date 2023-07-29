<?php

namespace App\Repositories\Statistic;
use App\Http\Requests\StatisticRequest;

use Illuminate\Http\Request;

interface StatisticInterface {
    // getAll
    public function getAllStatistics();
    //byKeyword
    public function getAllStatisticByKeyword($keyword);
    // findOne
    public function findById($id);
    // insertData
    public function createStatistic($request);
    // update
    public function updateStatistic($request, $id);
    // delete
    public function deleteStatistic($id);
}