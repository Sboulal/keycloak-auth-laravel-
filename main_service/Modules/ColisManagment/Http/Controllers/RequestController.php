<?php

namespace Modules\ColisManagment\Http\Controllers;

use Modules\Core\Entities\City;
use Modules\Core\Entities\Center;
use Nwidart\Modules\Routing\Controller;
use App\Http\Requests\SaveRequestRequest;
use Illuminate\Http\Request as HttpRequest;
use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Entities\DeliveryType;
use Modules\ColisManagment\Services\RequestService;
use Modules\ColisManagment\Entities\RegionTypePricing;
use Modules\ColisManagment\Transformers\RequestResource;

/**
 * @group Request Management
 *
 * APIs for managing package delivery requests
 */
class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Prepare Request Data
     * 
     * Get all necessary data for creating a request (cities, centers, delivery types, pricing)
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "cities": [
     *       {
     *         "id": 1,
     *         "name": "Casablanca"
     *       }
     *     ],
     *     "centers": {
     *       "Casablanca": [
     *         {
     *           "id": 1,
     *           "name": "Centre Maarif",
     *           "city": {
     *             "id": 1,
     *             "name": "Casablanca"
     *           }
     *         }
     *       ]
     *     },
     *     "delivery_types": [
     *       {
     *         "id": 1,
     *         "name": "Express",
     *         "description": "Livraison en 24h"
     *       }
     *     ],
     *     "pricing": {
     *       "Casablanca": [
     *         {
     *           "id": 1,
     *           "city_id": 1,
     *           "delivery_type_id": 1,
     *           "price": 25.00,
     *           "city": {
     *             "id": 1,
     *             "name": "Casablanca"
     *           },
     *           "deliveryType": {
     *             "id": 1,
     *             "name": "Express"
     *           }
     *         }
     *       ]
     *     }
     *   },
     *   "message": "Données chargées avec succès"
     * }
     * 
     * @response 500 scenario="Server Error" {
     *   "success": false,
     *   "message": "Erreur lors du chargement des données: Database connection failed"
     * }
     */
    public function prepareRequestData()
    {
        try {
            $cities = City::select('id', 'name')->get();

            $centers = Center::with('city:id,name')
                ->get()
                ->groupBy('city.name');

            $deliveryTypes = DeliveryType::get();

            $pricing = RegionTypePricing::with(['city:id,name', 'deliveryType:id,name'])
                ->get()
                ->groupBy('city.name');

            return response()->json([
                'success' => true,
                'data' => [
                    'cities' => $cities,
                    'centers' => $centers,
                    'delivery_types' => $deliveryTypes,
                    'pricing' => $pricing,
                ],
                'message' => 'Données chargées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search Address
     * 
     * Search for an address using query string (Google Maps integration placeholder)
     *
     * @bodyParam query string required The address search query. Must be at least 3 characters. Example: Casablanca Maarif
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "address": "Casablanca Maarif",
     *     "latitude": 33.5731,
     *     "longitude": -7.5898
     *   }
     * }
     * 
     * @response 422 scenario="Validation Error" {
     *   "message": "The query field is required.",
     *   "errors": {
     *     "query": [
     *       "The query field is required."
     *     ]
     *   }
     * }
     */
    public function searchAddress(HttpRequest $request)
    {
        $request->validate([
            'query' => 'required|string|min:3'
        ]);

        // TODO: Intégrer Google Maps Geocoding API
        return response()->json([
            'success' => true,
            'data' => [
                'address' => $request->query,
                'latitude' => 33.5731,
                'longitude' => -7.5898,
            ]
        ]);
    }

  
  

    /**
     * List Requests
     * 
     * Get a paginated list of requests with optional filters
     *
     * @queryParam status string Filter by status (pending, accepted, rejected, cancelled). Example: pending
     * @queryParam payment_status string Filter by payment status (paid, unpaid, partial). Example: paid
     * @queryParam date_from date Filter requests from this date. Example: 2024-01-01
     * @queryParam date_to date Filter requests until this date. Example: 2024-12-31
     * @queryParam center_id integer Filter by center ID. Example: 1
     * @queryParam source string Filter by source (web, mobile, api). Example: web
     * @queryParam last_id integer For pagination: load requests with ID less than this value. Example: 100
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "code": "REQ-2024-00001",
     *       "status": "pending",
     *       "payment_status": "unpaid",
     *       "created_at": "2024-11-05T10:30:00.000000Z",
     *       "user": {
     *         "id": 1,
     *         "name": "Ahmed Hassan",
     *         "email": "ahmed@example.com"
     *       },
     *       "center": {
     *         "id": 1,
     *         "name": "Centre Maarif"
     *       },
     *       "deliveryType": {
     *         "id": 1,
     *         "name": "Express"
     *       },
     *       "senderCity": {
     *         "id": 1,
     *         "name": "Casablanca"
     *       },
     *       "recipientCity": {
     *         "id": 2,
     *         "name": "Rabat"
     *       },
     *       "package": {
     *         "id": 1,
     *         "request_id": 1,
     *         "code": "PKG-2024-00001",
     *         "weight": 2.5
     *       },
     *       "payment": {
     *         "id": 1,
     *         "request_id": 1,
     *         "invoice_number": "INV-2024-00001"
     *       }
     *     }
     *   ],
     *   "moreToLoad": true,
     *   "message": "Liste des demandes chargée avec succès"
     * }
     * 
     * @response 500 scenario="Server Error" {
     *   "success": false,
     *   "message": "Erreur lors du chargement des demandes: Database error"
     * }
     */
    public function loadRequestsList(HttpRequest $request)
    {
        try {
            $query = Request::with([
                'user:id,name,email',
                'center:id,name',
                'deliveryType:id,name',
                'senderCity:id,name',
                'recipientCity:id,name',
                'package:id,request_id,code,weight',
                'payment:id,request_id,invoice_number'
            ]);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('center_id')) {
                $query->where('center_id', $request->center_id);
            }

            if ($request->has('source')) {
                $query->where('source', $request->source);
            }

            if ($request->has('last_id')) {
                $query->where('id', '<', $request->last_id);
            }

            $requests = $query->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $requests,
                'moreToLoad' => count($requests) === 20,
                'message' => 'Liste des demandes chargée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des demandes: ' . $e->getMessage()
            ], 500);
        }
    }

  
    /**
     * Show Request Details
     * 
     * Get detailed information about a specific request
     *
     * @urlParam id integer required The request ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "code": "REQ-2024-00001",
     *     "user_id": 1,
     *     "center_id": 1,
     *     "delivery_type_id": 1,
     *     "sender_city_id": 1,
     *     "recipient_city_id": 2,
     *     "sender_full_name": "Ahmed Hassan",
     *     "sender_phone": "+212612345678",
     *     "sender_address": "123 Rue Mohamed V",
     *     "recipient_full_name": "Fatima Zahra",
     *     "recipient_phone": "+212698765432",
     *     "recipient_address": "456 Avenue Hassan II",
     *     "weight": 2.5,
     *     "status": "pending",
     *     "payment_status": "unpaid",
     *     "user": {
     *       "id": 1,
     *       "name": "Ahmed Hassan",
     *       "email": "ahmed@example.com"
     *     },
     *     "center": {
     *       "id": 1,
     *       "name": "Centre Maarif",
     *       "city": {
     *         "id": 1,
     *         "name": "Casablanca"
     *       }
     *     },
     *     "deliveryType": {
     *       "id": 1,
     *       "name": "Express"
     *     },
     *     "senderCity": {
     *       "id": 1,
     *       "name": "Casablanca"
     *     },
     *     "recipientCity": {
     *       "id": 2,
     *       "name": "Rabat"
     *     },
     *     "package": {
     *       "id": 1,
     *       "code": "PKG-2024-00001"
     *     },
     *     "payment": {
     *       "id": 1,
     *       "invoice_number": "INV-2024-00001"
     *     },
     *     "validator": {
     *       "id": 5,
     *       "name": "Manager Name"
     *     }
     *   },
     *   "message": "Détails de la demande chargés avec succès"
     * }
     * 
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Erreur lors du chargement des détails: Request not found"
     * }
     */
    public function show($id)
    {
        try {
            $request = Request::with([
                'user',
                'center.city',
                'deliveryType',
                'senderCity',
                'recipientCity',
                'package',
                'payment',
                'validator'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $request,
                'message' => 'Détails de la demande chargés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des détails: ' . $e->getMessage()
            ], 500);
        }
    }


}