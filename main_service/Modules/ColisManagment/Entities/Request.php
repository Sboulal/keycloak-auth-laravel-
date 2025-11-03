<?php

namespace Modules\ColisManagment\Entities;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'status',
        'payment_status',
        'price',
        'source'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
