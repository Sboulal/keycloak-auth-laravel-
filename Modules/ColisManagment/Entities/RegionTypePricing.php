<?php

namespace Modules\ColisManagment\Entities;

use Illuminate\Database\Eloquent\Model;

class RegionTypePricing extends Model
{
    protected $table = 'region_type_pricing'; // Correspond à la migration
    
    protected $fillable = [
        'city_id',
        'delivery_type_id',
        'base_price',
        'price_per_km',
        'price_per_kg',
        'is_active'
    ];

    protected $casts = [
        'base_price' => 'float',
        'price_per_km' => 'float',
        'price_per_kg' => 'float',
        'is_active' => 'boolean',
    ];

    // Relations
    public function city()
    {
        return $this->belongsTo(\Modules\Core\Entities\City::class);
    }

    public function deliveryType()
    {
        return $this->belongsTo(\Modules\ColisManagment\Entities\DeliveryType::class);
    }
}