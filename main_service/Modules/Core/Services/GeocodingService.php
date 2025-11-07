<?php
// Modules/ColisManagment/Services/RequestService.php
namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Modules\ColisManagment\Entities\Package;
use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Entities\RegionTypePricing;

class GeocodingService
{
    protected $googleApiKey;
    protected $orsApiKey;

    public function __construct()
    {
        $this->googleApiKey = config('services.google_maps.api_key');
        $this->orsApiKey = config('services.openrouteservice.api_key');
    }

    /**
     * Rechercher une adresse avec Google Maps Geocoding API
     */
    public function searchAddress($query)
    {
        // Cache pour 1 heure
        $cacheKey = 'geocode_' . md5($query);
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key' => $this->googleApiKey,
                    'language' => 'fr',
                    'region' => 'ma'
                ]);

                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    
                    return [
                        'success' => true,
                        'address' => $result['formatted_address'],
                        'latitude' => $result['geometry']['location']['lat'],
                        'longitude' => $result['geometry']['location']['lng'],
                        'place_id' => $result['place_id'],
                        'types' => $result['types'],
                        'components' => $result['address_components'] ?? []
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Aucune adresse trouvée'
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Obtenir l'adresse à partir des coordonnées (reverse geocoding)
     */
    public function reverseGeocode($latitude, $longitude)
    {
        $cacheKey = "reverse_geocode_{$latitude}_{$longitude}";
        
        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $this->googleApiKey,
                    'language' => 'fr'
                ]);

                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    return [
                        'success' => true,
                        'address' => $data['results'][0]['formatted_address']
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Adresse non trouvée'
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Calculer un itinéraire avec OpenRouteService
     */
    public function getRoute($startLat, $startLng, $endLat, $endLng)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->orsApiKey,
                'Accept' => 'application/json'
            ])->get('https://api.openrouteservice.org/v2/directions/driving-car', [
                'start' => "{$startLng},{$startLat}",
                'end' => "{$endLng},{$endLat}"
            ]);

            $data = $response->json();

            if (isset($data['features'][0]['geometry']['coordinates'])) {
                $coordinates = collect($data['features'][0]['geometry']['coordinates'])
                    ->map(fn($coord) => [
                        'lat' => $coord[1],
                        'lng' => $coord[0]
                    ]);

                $properties = $data['features'][0]['properties']['segments'][0] ?? [];

                return [
                    'success' => true,
                    'route' => $coordinates->toArray(),
                    'distance' => $properties['distance'] ?? null, // mètres
                    'duration' => $properties['duration'] ?? null, // secondes
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de calculer l\'itinéraire'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculer la distance entre deux points (méthode Haversine)
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2); // km
    }
}