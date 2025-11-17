<?php
namespace Modules\Core\Entities;
use Illuminate\Database\Eloquent\Model;
use Modules\ColisManagment\Entities\Delivery;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackingPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'delivery_id',
        'latitude',
        'longitude',
        'speed',
        'accuracy',
        'battery_level',
        'recorded_at'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'accuracy' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Calculer la distance entre deux points (en km)
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}