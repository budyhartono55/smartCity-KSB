<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class News extends Model
{
    use HasFactory, GenUid;
    protected $guarded = [];
    protected $table = 'news';
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // relasi one to many (comment)
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function categories()
    {
        return $this->belongsToMany(User::class);
    }
}
