<?php

namespace Modules\ColisManagment\Entities;

use Modules\Core\Entities\Driver;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Entities\TrackingPoint;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_address',
        'status',
        'started_at',
        'completed_at',
        'total_distance',
        'estimated_duration',
        // ... autres champs
    ];

    protected $casts = [
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'delivery_latitude' => 'float',
        'delivery_longitude' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_distance' => 'float',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function trackingPoints()
    {
        return $this->hasMany(TrackingPoint::class);
    }

    /**
     * Démarrer la livraison
     */
    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Terminer la livraison
     */
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Calculer la distance totale parcourue
        $this->calculateTotalDistance();
    }

    /**
     * Calculer la distance totale parcourue
     */
    protected function calculateTotalDistance()
    {
        $points = $this->trackingPoints()
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude']);

        $totalDistance = 0;
        for ($i = 1; $i < $points->count(); $i++) {
            $totalDistance += TrackingPoint::calculateDistance(
                $points[$i - 1]->latitude,
                $points[$i - 1]->longitude,
                $points[$i]->latitude,
                $points[$i]->longitude
            );
        }

        $this->update(['total_distance' => $totalDistance]);
    }
}