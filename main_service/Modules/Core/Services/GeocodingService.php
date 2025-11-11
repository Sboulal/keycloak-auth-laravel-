<?php
// Modules/Core/Services/GeocodingService.php
namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Modules\ColisManagment\Entities\Package;
use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Entities\RegionTypePricing;

class GeocodingService
{
    protected $orsApiKey;
    protected $nominatimUrl = 'https://nominatim.openstreetmap.org';

    public function __construct()
    {
        $this->orsApiKey = config('services.openrouteservice.api_key');
    }

    /**
     * Rechercher une adresse avec Nominatim (OpenStreetMap) - GRATUIT
     */
    public function searchAddress($query)
    {
        $cacheKey = 'geocode_' . md5($query);
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            try {
                // Nominatim nécessite un User-Agent
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Laravel') . ' Geocoding'
                ])->get($this->nominatimUrl . '/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'countrycodes' => 'ma', // Limité au Maroc
                    'accept-language' => 'fr'
                ]);

                $data = $response->json();

                if (!empty($data)) {
                    $result = $data[0];
                    
                    return [
                        'success' => true,
                        'address' => $result['display_name'],
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'place_id' => $result['place_id'],
                        'type' => $result['type'] ?? null,
                        'importance' => $result['importance'] ?? null,
                        'components' => $result['address'] ?? []
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Aucune adresse trouvée'
                ];

            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Obtenir l'adresse à partir des coordonnées (reverse geocoding) - GRATUIT
     */
    public function reverseGeocode($latitude, $longitude)
    {
        $cacheKey = "reverse_geocode_{$latitude}_{$longitude}";
        
        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Laravel') . ' Geocoding'
                ])->get($this->nominatimUrl . '/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'fr'
                ]);

                $data = $response->json();

                if (!empty($data) && isset($data['display_name'])) {
                    return [
                        'success' => true,
                        'address' => $data['display_name'],
                        'components' => $data['address'] ?? []
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Adresse non trouvée'
                ];

            } catch (\Exception $e) {
                Log::error('Reverse geocoding error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Calculer un itinéraire avec OpenRouteService - GRATUIT (avec limite)
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
                    'distance_km' => isset($properties['distance']) ? round($properties['distance'] / 1000, 2) : null,
                    'duration_minutes' => isset($properties['duration']) ? round($properties['duration'] / 60, 2) : null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de calculer l\'itinéraire'
            ];

        } catch (\Exception $e) {
            Log::error('Route calculation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculer la distance entre deux points (méthode Haversine) - 100% GRATUIT
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

    /**
     * Autocomplete d'adresse avec Nominatim - GRATUIT
     */
    public function autocomplete($query, $limit = 5)
    {
        $cacheKey = 'autocomplete_' . md5($query . $limit);
        
        return Cache::remember($cacheKey, 1800, function () use ($query, $limit) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name', 'Laravel') . ' Geocoding'
                ])->get($this->nominatimUrl . '/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => $limit,
                    'countrycodes' => 'ma',
                    'accept-language' => 'fr'
                ]);

                $data = $response->json();

                if (!empty($data)) {
                    return [
                        'success' => true,
                        'suggestions' => collect($data)->map(function ($item) {
                            return [
                                'address' => $item['display_name'],
                                'latitude' => (float) $item['lat'],
                                'longitude' => (float) $item['lon'],
                                'type' => $item['type'] ?? null,
                                'city' => $item['address']['city'] ?? $item['address']['town'] ?? null,
                                'region' => $item['address']['state'] ?? null,
                            ];
                        })->toArray()
                    ];
                }

                return [
                    'success' => false,
                    'suggestions' => []
                ];

            } catch (\Exception $e) {
                Log::error('Autocomplete error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'suggestions' => []
                ];
            }
        });
    }

    /**
     * Respecter les limites d'utilisation de Nominatim
     * (1 requête par seconde maximum)
     */
    private function respectRateLimit()
    {
        // Ajouter un délai si nécessaire
        usleep(1000000); // 1 seconde
    }
}