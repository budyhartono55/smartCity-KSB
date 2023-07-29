<?php

namespace App\Repositories\Setting;

interface SettingInterface
{
    public function getAll();
    public function getById($id);
    public function save($request);
    public function update($request, $id);
    public function delete($id);
}
