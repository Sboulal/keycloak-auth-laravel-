<?php

namespace Modules\ColisManagment\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'code',
        'weight',
        'length',
        'width',
        'height',
        'content_type',
        'description',
        'declared_value',
        'distance',
        'notes',
        'payment_method',
        'source',
    ];

    protected $casts = [
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'declared_value' => 'float',
        'recipient_latitude' => 'float',
        'recipient_longitude' => 'float',
        'distance' => 'float',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function recipientCity()
    {
        return $this->belongsTo(\Modules\Core\Entities\City::class, 'recipient_city_id');
    }
}