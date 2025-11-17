<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $fillable = ['name', 'city_id', 'is_active'];
    protected $table = 'centers'; // ou ton nom de table
    public function city()
{
    return $this->belongsTo(City::class, 'city_id');
}
}