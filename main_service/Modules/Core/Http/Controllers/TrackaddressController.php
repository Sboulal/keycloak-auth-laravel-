<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\TrackingPoint;
use Modules\Core\Entities\Driver;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Services\GeocodingService;
use Illuminate\Contracts\Support\Renderable;
use Modules\ColisManagment\Entities\Delivery;

class TrackaddressController extends Controller
{
    protected $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    /**
     * Rechercher une adresse avec Google Maps Geocoding API
     * GET /api/tracking/search-address?query=...
     */
    public function searchAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->geocodingService->searchAddress($request->query);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Obtenir l'adresse à partir des coordonnées
     * GET /api/tracking/reverse-geocode?lat=...&lng=...
     */
    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->geocodingService->reverseGeocode($request->lat, $request->lng);

        return response()->json($result);
    }

    /**
     * Obtenir tous les livreurs actifs avec leurs positions
     * GET /api/tracking/active-drivers
     */
    public function getActiveDrivers()
    {
        $drivers = Driver::with(['user', 'activeDeliveries.packages.request'])
            ->where('is_online', true)
            ->get();

        $driversData = $drivers->map(function ($driver) {
            $activeDelivery = $driver->activeDeliveries->first();
            
            return [
                'id' => $driver->id,
                'name' => $driver->user->name ?? "Livreur #{$driver->id}",
                'vehicle_type' => $driver->vehicle_type,
                'is_available' => $driver->isAvailable(),
                'current_position' => [
                    'lat' => $driver->last_latitude,
                    'lng' => $driver->last_longitude,
                    'updated_at' => $driver->last_position_update
                ],
                'active_delivery' => $activeDelivery ? [
                    'id' => $activeDelivery->id,
                    'status' => $activeDelivery->status,
                    'pickup' => [
                        'lat' => $activeDelivery->pickup_latitude,
                        'lng' => $activeDelivery->pickup_longitude,
                        'address' => $activeDelivery->pickup_address
                    ],
                    'destination' => [
                        'lat' => $activeDelivery->delivery_latitude,
                        'lng' => $activeDelivery->delivery_longitude,
                        'address' => $activeDelivery->delivery_address
                    ],
                    'started_at' => $activeDelivery->started_at,
                    'estimated_duration' => $activeDelivery->estimated_duration
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $driversData->count(),
            'drivers' => $driversData
        ]);
    }

    /**
     * Enregistrer un point de tracking
     * POST /api/tracking/record-point
     * Appelé par l'application mobile du livreur toutes les 20 secondes
     */
    public function recordTrackingPoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'delivery_id' => 'required|exists:deliveries,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'accuracy' => 'nullable|numeric|min:0',
            'battery_level' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Enregistrer le point de tracking
            $trackingPoint = TrackingPoint::create([
                'driver_id' => $request->driver_id,
                'delivery_id' => $request->delivery_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'speed' => $request->speed,
                'accuracy' => $request->accuracy,
                'battery_level' => $request->battery_level,
                'recorded_at' => now()
            ]);

            // Mettre à jour la position actuelle du driver
            $driver = Driver::find($request->driver_id);
            $driver->updatePosition($request->latitude, $request->longitude);

            // Vérifier si le livreur est proche de la destination
            $delivery = Delivery::find($request->delivery_id);
            $distanceToDestination = $this->geocodingService->calculateDistance(
                $request->latitude,
                $request->longitude,
                $delivery->delivery_latitude,
                $delivery->delivery_longitude
            );

            $nearDestination = $distanceToDestination < 0.1; // 100 mètres

            DB::commit();

            return response()->json([
                'success' => true,
                'tracking_point' => [
                    'id' => $trackingPoint->id,
                    'recorded_at' => $trackingPoint->recorded_at
                ],
                'driver_position_updated' => true,
                'distance_to_destination' => round($distanceToDestination, 2), // km
                'near_destination' => $nearDestination
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique de tracking d'une livraison
     * GET /api/tracking/delivery/{deliveryId}
     */
    public function getDeliveryTracking($deliveryId)
    {
        $delivery = Delivery::with(['trackingPoints' => function ($query) {
            $query->orderBy('recorded_at', 'asc');
        }, 'driver.user'])->find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Livraison non trouvée'
            ], 404);
        }

        $trackingPoints = $delivery->trackingPoints->map(function ($point) {
            return [
                'lat' => $point->latitude,
                'lng' => $point->longitude,
                'speed' => $point->speed,
                'accuracy' => $point->accuracy,
                'recorded_at' => $point->recorded_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'success' => true,
            'delivery' => [
                'id' => $delivery->id,
                'status' => $delivery->status,
                'driver' => [
                    'id' => $delivery->driver->id,
                    'name' => $delivery->driver->user->name ?? "Livreur #{$delivery->driver->id}"
                ],
                'pickup' => [
                    'lat' => $delivery->pickup_latitude,
                    'lng' => $delivery->pickup_longitude,
                    'address' => $delivery->pickup_address
                ],
                'destination' => [
                    'lat' => $delivery->delivery_latitude,
                    'lng' => $delivery->delivery_longitude,
                    'address' => $delivery->delivery_address
                ],
                'started_at' => $delivery->started_at,
                'completed_at' => $delivery->completed_at,
                'total_distance' => $delivery->total_distance,
                'tracking_points_count' => $trackingPoints->count()
            ],
            'tracking_points' => $trackingPoints
        ]);
    }

    /**
     * Obtenir l'itinéraire entre deux points
     * GET /api/tracking/route?start_lat=...&start_lng=...&end_lat=...&end_lng=...
     */
    public function getRoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lng' => 'required|numeric|between:-180,180',
            'end_lat' => 'required|numeric|between:-90,90',
            'end_lng' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->geocodingService->getRoute(
            $request->start_lat,
            $request->start_lng,
            $request->end_lat,
            $request->end_lng
        );

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Démarrer une livraison
     * POST /api/tracking/delivery/{deliveryId}/start
     */
    public function startDelivery(Request $request, $deliveryId)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'current_lat' => 'required|numeric',
            'current_lng' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $delivery = Delivery::findOrFail($deliveryId);

            if ($delivery->status === 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette livraison est déjà en cours'
                ], 400);
            }

            // Calculer l'itinéraire et la durée estimée
            $route = $this->geocodingService->getRoute(
                $request->current_lat,
                $request->current_lng,
                $delivery->delivery_latitude,
                $delivery->delivery_longitude
            );

            DB::beginTransaction();

            $delivery->update([
                'driver_id' => $request->driver_id,
                'status' => 'in_progress',
                'started_at' => now(),
                'estimated_duration' => $route['duration'] ?? null
            ]);

            // Enregistrer le premier point de tracking
            TrackingPoint::create([
                'driver_id' => $request->driver_id,
                'delivery_id' => $deliveryId,
                'latitude' => $request->current_lat,
                'longitude' => $request->current_lng,
                'recorded_at' => now()
            ]);

            // Mettre à jour la position du driver
            $driver = Driver::find($request->driver_id);
            $driver->updatePosition($request->current_lat, $request->current_lng);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Livraison démarrée avec succès',
                'delivery' => [
                    'id' => $delivery->id,
                    'status' => $delivery->status,
                    'started_at' => $delivery->started_at,
                    'estimated_duration' => $delivery->estimated_duration,
                    'estimated_distance' => $route['distance'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminer une livraison
     * POST /api/tracking/delivery/{deliveryId}/complete
     */
    public function completeDelivery(Request $request, $deliveryId)
    {
        try {
            $delivery = Delivery::findOrFail($deliveryId);

            if ($delivery->status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette livraison n\'est pas en cours'
                ], 400);
            }

            $delivery->complete();

            return response()->json([
                'success' => true,
                'message' => 'Livraison terminée avec succès',
                'delivery' => [
                    'id' => $delivery->id,
                    'status' => $delivery->status,
                    'completed_at' => $delivery->completed_at,
                    'total_distance' => $delivery->total_distance,
                    'duration' => $delivery->started_at->diffInMinutes($delivery->completed_at)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut en ligne du livreur
     * POST /api/tracking/driver/{driverId}/toggle-online
     */
    public function toggleDriverOnline($driverId)
    {
        try {
            $driver = Driver::findOrFail($driverId);
            $driver->is_online = !$driver->is_online;
            $driver->save();

            return response()->json([
                'success' => true,
                'driver_id' => $driver->id,
                'is_online' => $driver->is_online
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}

