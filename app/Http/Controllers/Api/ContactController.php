<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Contact\ContactInterface;

class ContactController extends Controller
{
    private $contactRepository;

    public function __construct(ContactInterface $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }


    public function getAll()
    {

        return $this->contactRepository->getAll();
    }

    public function getById($id)
    {

        return $this->contactRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->contactRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->contactRepository->update($request, $id);
    }

    public function delete($id)
    {
        return $this->contactRepository->delete($id);
    }
}
