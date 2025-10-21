<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'livreur_name',
        'latitude',
        'longitude',
        'road',
        'city',
        'postcode',
        'country',
        'full_address'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
