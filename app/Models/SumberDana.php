<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SumberDana extends Model
{
    protected $table = 'sumber_dana';
    protected $guarded = [];

    public function utangCicilan()
    {
        return $this->hasMany(UtangCicilan::class);
    }
}
