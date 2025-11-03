<?php

namespace Modules\ColisManagment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\ColisManagment\Services\RequestService;
use Modules\ColisManagment\Resources\RequestResource;
use Modules\ColisManagment\Resources\RequestListResource;
use Modules\ColisManagment\Http\Requests\StoreRequestRequest;

/**
 * @group Colis Management - Requests
 *
 * APIs for creating, managing, and tracking delivery requests.
 * 
 * All endpoints below require authentication (`Bearer token`).
 */
class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Prepare data for creating a new request
     *
     * Get all necessary data (cities, centers, delivery types, user info, etc.)
     *
     * @authenticated
     * @response status=200 scenario="success" {
     *  "success": true,
     *  "data": {
     *    "cities": [{"id": 1, "name": "Casablanca"}],
     *    "centers": {"1": [{"id": 2, "name": "Center A"}]},
     *    "delivery_types": [{"id": 1, "name": "Express", "pricing_by_city": []}],
     *    "user_data": {"name": "John Doe", "email": "john@example.com"}
     *  }
     * }
     */
    public function prepareData(Request $request): JsonResponse
    {
        $data = $this->requestService->prepareRequestData($request->user());
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Search for an address
     *
     * Use geocoding to find coordinates from an address string.
     *
     * @authenticated
     * @bodyParam address string required The address to search for. Example: "Boulevard Zerktouni, Casablanca"
     * @response 200 {
     *  "success": true,
     *  "data": {"latitude": 33.589886, "longitude": -7.603869}
     * }
     */
    public function searchAddress(Request $request): JsonResponse
    {
        $request->validate(['address' => 'required|string|min:5']);
        $addressData = $this->requestService->searchAddress($request->address);
        return response()->json(['success' => true, 'data' => $addressData]);
    }

    /**
     * Create or update a delivery request
     *
     * Create a new delivery request or update an existing one.
     *
     * @authenticated
     * @bodyParam request.center_id integer required The pickup center ID.
     * @bodyParam request.receiver_address string required The delivery address.
     * @bodyParam request.delivery_type_id integer required The chosen delivery type.
     * @bodyParam package.weight float required Package weight in KG.
     * @bodyParam package.length float optional Package length in cm.
     * @bodyParam package.width float optional Package width in cm.
     * @bodyParam package.height float optional Package height in cm.
     * @bodyParam payment.method integer required Payment method (0 = online, 1 = cash).
     * @response 201 scenario="created" {
     *   "success": true,
     *   "message": "Demande créée avec succès.",
     *   "data": {
     *      "id": 1,
     *      "code_request": "D-uuid",
     *      "amount": "50.00",
     *      "status": 0
     *   }
     * }
     */
    public function save(StoreRequestRequest $request): JsonResponse
    {
        $result = $this->requestService->saveRequest($request->validated(), $request->user());
        return response()->json([
            'success' => true,
            'message' => $request->input('request_id')
                ? 'Demande mise à jour avec succès'
                : 'Demande créée avec succès. Veuillez procéder au paiement.',
            'data' => new RequestResource($result['request'])
        ], 201);
    }

    /**
     * Change request status
     *
     * Update the status of a request (pending, validated, cancelled).
     *
     * @authenticated
     * @urlParam id integer required The ID of the request.
     * @bodyParam status integer required Status code (0 = pending, 1 = validated, 2 = cancelled).
     * @response 200 {
     *   "success": true,
     *   "message": "La demande a été validée avec succès",
     *   "data": {"id": 1, "status": 1}
     * }
     */
    public function changeStatus(Request $request, $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1,2']);
        $updatedRequest = $this->requestService->changeRequestStatus($id, $request->status);
        return response()->json(['success' => true, 'data' => new RequestResource($updatedRequest)]);
    }

    /**
     * Apply a payment to a request
     *
     * Register payment for a request and send an invoice by email.
     *
     * @authenticated
     * @urlParam id integer required The ID of the request.
     * @bodyParam method integer required Payment method (0 = online, 1 = cash).
     * @bodyParam amount numeric required The payment amount.
     * @bodyParam payment_details object optional Additional payment data.
     * @response 200 {
     *   "success": true,
     *   "message": "Paiement enregistré avec succès.",
     *   "data": {
     *     "payment": {"id": 5, "amount": "120.00", "status": 1},
     *     "invoice_url": "https://api.example.com/invoices/5/download"
     *   }
     * }
     */
    public function applyPayment(Request $request, $id): JsonResponse
    {
        $request->validate([
            'method' => 'required|integer|in:0,1',
            'amount' => 'required|numeric|min:0',
            'payment_details' => 'nullable|array'
        ]);
        $result = $this->requestService->applyPayment($id, $request->all());
        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès.',
            'data' => [
                'payment' => $result['payment'],
                'request' => new RequestResource($result['request']),
                'invoice_url' => route('invoices.download', $result['payment']->id)
            ]
        ]);
    }

    /**
     * Change payment status
     *
     * @authenticated
     * @urlParam id integer required The ID of the request.
     * @bodyParam status integer required Payment status (0 = unpaid, 1 = paid, 2 = refunded).
     * @response 200 {
     *   "success": true,
     *   "message": "Le paiement a été confirmé",
     *   "data": {"id": 1, "payment_status": 1}
     * }
     */
    public function changePaymentStatus(Request $request, $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1,2']);
        $updatedRequest = $this->requestService->changePaymentStatus($id, $request->status);
        return response()->json(['success' => true, 'data' => new RequestResource($updatedRequest)]);
    }

    /**
     * Get list of requests
     *
     * Retrieve paginated requests with optional filters (status, date, center, etc.)
     *
     * @authenticated
     * @queryParam status integer Filter by status.
     * @queryParam payment_status integer Filter by payment status.
     * @queryParam date_from date Filter from this date. Example: 2025-01-01
     * @queryParam date_to date Filter until this date. Example: 2025-01-31
     * @queryParam center_id integer Filter by center ID.
     * @queryParam last_id integer For pagination: last loaded ID.
     * @response 200 {
     *   "success": true,
     *   "data": [{"id": 1, "code_request": "D-uuid"}],
     *   "meta": {"more_to_load": true, "total_loaded": 20}
     * }
     */
    public function loadList(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'payment_status' => $request->input('payment_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'center_id' => $request->input('center_id'),
            'last_id' => $request->input('last_id'),
            'per_page' => 20
        ];

        $result = $this->requestService->loadRequestsList($filters, $request->user());
        return response()->json([
            'success' => true,
            'data' => RequestListResource::collection($result['data']),
            'meta' => ['more_to_load' => $result['more_to_load']]
        ]);
    }

    /**
     * Search requests
     *
     * Search for delivery requests using one or more keywords.
     *
     * @authenticated
     * @bodyParam keywords array required List of keywords to search. Example: ["John", "D-12345"]
     * @response 200 {
     *   "success": true,
     *   "data": [{"id": 1, "code_request": "D-uuid", "status": 1}],
     *   "meta": {"total_found": 5}
     * }
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['keywords' => 'required|array|min:1', 'keywords.*' => 'string|min:2']);
        $filters = $request->only(['status', 'payment_status', 'center_id']);
        $results = $this->requestService->searchRequests($request->keywords, $filters, $request->user());
        return response()->json([
            'success' => true,
            'data' => RequestListResource::collection($results),
            'meta' => ['total_found' => count($results)]
        ]);
    }

    /**
     * Get request details
     *
     * Retrieve complete information about a specific delivery request.
     *
     * @authenticated
     * @urlParam id integer required The ID of the request.
     * @response 200 {
     *   "success": true,
     *   "data": {
     *      "id": 1,
     *      "code_request": "D-uuid",
     *      "status": 1,
     *      "package": {"id": 3, "weight": 2.5},
     *      "payment": {"id": 8, "status": 1}
     *   }
     * }
     */
    public function show($id): JsonResponse
    {
        $request = $this->requestService->getRequestDetails($id);
        return response()->json(['success' => true, 'data' => new RequestResource($request)]);
    }
}
