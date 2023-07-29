<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class Regulasi extends Model
{
    use HasFactory, GenUid;
    protected $guarded = [];
    protected $table = 'regulasi';
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
