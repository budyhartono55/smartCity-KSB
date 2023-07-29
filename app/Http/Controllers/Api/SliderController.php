<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Slider\SliderInterface;

class SliderController extends Controller
{
    private $sliderRepository;

    public function __construct(SliderInterface $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }


    public function getAll()
    {

        return $this->sliderRepository->getAll();
    }

    public function getById($id)
    {

        return $this->sliderRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->sliderRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->sliderRepository->update($request, $id);
    }

    public function delete($id)
    {
        return $this->sliderRepository->delete($id);
    }
}
