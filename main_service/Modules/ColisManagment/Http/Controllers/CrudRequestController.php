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

class CrudRequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Create or Update Request
     * 
     * Create a new delivery request or update an existing one
     *
     * @bodyParam id integer Optional request ID for updating. Example: 1
     * @bodyParam center_id integer required The center ID. Example: 1
     * @bodyParam delivery_type_id integer required The delivery type ID. Example: 1
     * @bodyParam sender_city_id integer required The sender city ID. Example: 1
     * @bodyParam recipient_city_id integer required The recipient city ID. Example: 2
     * @bodyParam sender_full_name string required Sender's full name. Example: Ahmed Hassan
     * @bodyParam sender_phone string required Sender's phone number. Example: +212612345678
     * @bodyParam sender_address string required Sender's address. Example: 123 Rue Mohamed V
     * @bodyParam recipient_full_name string required Recipient's full name. Example: Fatima Zahra
     * @bodyParam recipient_phone string required Recipient's phone number. Example: +212698765432
     * @bodyParam recipient_address string required Recipient's address. Example: 456 Avenue Hassan II
     * @bodyParam weight number required Package weight in kg. Example: 2.5
     * @bodyParam description string Optional package description. Example: Documents importants
     * @bodyParam declared_value number Optional declared value. Example: 500.00
     * @bodyParam source string Source of request (web/mobile/api). Example: web
     * 
     * @response 201 {
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
     *     "created_at": "2024-11-05T10:30:00.000000Z"
     *   },
     *   "message": "Demande créée avec succès. Code: REQ-2024-00001"
     * }
     * 
     * @response 422 scenario="Validation Error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "sender_full_name": [
     *       "The sender full name field is required."
     *     ]
     *   }
     * }
     * 
     * @response 500 scenario="Server Error" {
     *   "success": false,
     *   "message": "Erreur lors de l'enregistrement de la demande: Internal server error"
     * }
     */
    public function saveRequest(SaveRequestRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            if ($request->has('id') && $request->id) {
                $requestModel = $this->requestService->updateRequest($request->id, $data, $user);
                $message = 'Demande mise à jour avec succès';
            } else {
                $requestModel = $this->requestService->createRequest($data, $user);
                $message = 'Demande créée avec succès. Code: ' . $requestModel->code;
            }

            return response()->json([
                'success' => true,
                'data' => $requestModel,
                'message' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la demande: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change Request Status
     * 
     * Update the status of a request
     *
     * @urlParam id integer required The request ID. Example: 1
     * @bodyParam status string required The new status. Must be one of: pending, accepted, rejected, cancelled. Example: accepted
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "code": "REQ-2024-00001",
     *     "status": "accepted",
     *     "validated_by": 5,
     *     "validated_at": "2024-11-05T10:35:00.000000Z"
     *   },
     *   "message": "Statut de la demande changé avec succès"
     * }
     * 
     * @response 422 scenario="Invalid Status" {
     *   "message": "The selected status is invalid.",
     *   "errors": {
     *     "status": [
     *       "The selected status is invalid."
     *     ]
     *   }
     * }
     * 
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Erreur lors du changement de statut: Request not found"
     * }
     */
    public function changeStatus(HttpRequest $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,rejected,cancelled'
        ]);

        try {
            $user = auth()->user();
            $requestModel = $this->requestService->changeStatus($id, $request->status, $user);

            return response()->json([
                'success' => true,
                'data' => $requestModel,
                'message' => 'Statut de la demande changé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut: ' . $e->getMessage()
            ], 500);
        }
    }

 

    /**
     * Search Requests
     * 
     * Search requests by keywords (code, sender name, recipient name, user name/email)
     *
     * @bodyParam keywords array required Array of search keywords. Example: ["REQ-2024", "Ahmed"]
     * @bodyParam keywords.* string Each keyword to search for. Example: REQ-2024
     * @bodyParam status string Optional status filter. Example: pending
     * @bodyParam payment_status string Optional payment status filter. Example: unpaid
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "code": "REQ-2024-00001",
     *       "sender_full_name": "Ahmed Hassan",
     *       "recipient_full_name": "Fatima Zahra",
     *       "status": "pending",
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
     *       "package": {
     *         "id": 1,
     *         "request_id": 1,
     *         "code": "PKG-2024-00001"
     *       }
     *     }
     *   ],
     *   "count": 1,
     *   "message": "1 résultat(s) trouvé(s)"
     * }
     * 
     * @response 422 scenario="Validation Error" {
     *   "message": "The keywords field is required.",
     *   "errors": {
     *     "keywords": [
     *       "The keywords field is required."
     *     ]
     *   }
     * }
     */
    public function searchRequest(HttpRequest $request)
    {
        $request->validate([
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'string'
        ]);

        try {
            $query = Request::with([
                'user:id,name,email',
                'center:id,name',
                'deliveryType:id,name',
                'package:id,request_id,code'
            ]);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $query->where(function ($q) use ($request) {
                foreach ($request->keywords as $keyword) {
                    $q->orWhere('code', 'LIKE', "%{$keyword}%")
                      ->orWhere('sender_full_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('recipient_full_name', 'LIKE', "%{$keyword}%")
                      ->orWhereHas('user', function ($userQuery) use ($keyword) {
                          $userQuery->where('name', 'LIKE', "%{$keyword}%")
                                   ->orWhere('email', 'LIKE', "%{$keyword}%");
                      });
                }
            });

            $requests = $query->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $requests,
                'count' => count($requests),
                'message' => count($requests) . ' résultat(s) trouvé(s)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Delete Request
     * 
     * Soft delete a request (only if not validated or package is still pending)
     *
     * @urlParam id integer required The request ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Demande supprimée avec succès"
     * }
     * 
     * @response 422 scenario="Cannot Delete" {
     *   "success": false,
     *   "message": "Impossible de supprimer une demande validée avec un colis en cours de traitement"
     * }
     * 
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Erreur lors de la suppression: Request not found"
     * }
     */
    public function destroy($id)
    {
        try {
            $request = Request::findOrFail($id);

            if (in_array($request->status, ['accepted']) && $request->package->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une demande validée avec un colis en cours de traitement'
                ], 422);
            }

            $request->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}