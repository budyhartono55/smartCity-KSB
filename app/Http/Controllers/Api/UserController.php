<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\User\UserInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $userRepository;

    public function __construct(UserInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    public function getAll()
    {

        return $this->userRepository->getAll();
    }
    public function getAllTrash()
    {
        return $this->userRepository->getAllTrash();
    }

    public function getById($id)
    {

        return $this->userRepository->getById($id);
    }

    public function save(Request $request)
    {
        return $this->userRepository->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->userRepository->update($request, $id);
    }

    public function deleteSementara($id)
    {
        return $this->userRepository->deleteSementara($id);
    }
    public function deletePermanent($id)
    {
        return $this->userRepository->deletePermanent($id);
    }
    public function restore()
    {
        return $this->userRepository->restore();
    }
    public function restoreById($id)
    {
        return $this->userRepository->restoreById($id);
    }
    public function changePassword(Request $request, $id)
    {
        return $this->userRepository->changePassword($request, $id);
    }
    public function resetPassword($id)
    {
        return $this->userRepository->resetPassword($id);
    }
}
