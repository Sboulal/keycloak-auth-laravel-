<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\UserManagment\Entities\User;
use Modules\ColisManagment\Entities\Delivery;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_driver',
        'user_id',
         'center_id',
        'vehicle_type',
        'last_latitude',
        'last_longitude',
        'last_position_update',
        'is_online',
      
    ];

    protected $casts = [
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_position_update' => 'datetime',
        'is_online' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function trackingPoints()
    {
        return $this->hasMany(TrackingPoint::class);
    }

    public function centers()
    {
        return $this->belongsToMany(Center::class);
    }

    /**
     * Obtenir les livraisons actives du livreur
     */
    public function activeDeliveries()
    {
        return $this->deliveries()->where('status', 'in_progress');
    }

    /**
     * Vérifier si le livreur est disponible
     */
    public function isAvailable()
    {
        return $this->is_online && $this->activeDeliveries()->count() === 0;
    }

    /**
     * Mettre à jour la position du livreur
     */
    public function updatePosition($latitude, $longitude)
    {
        $this->update([
            'last_latitude' => $latitude,
            'last_longitude' => $longitude,
            'last_position_update' => now(),
        ]);
    }
}