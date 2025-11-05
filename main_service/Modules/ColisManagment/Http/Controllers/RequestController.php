<?php

// namespace Modules\ColisManagment\Http\Controllers\Controller;

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




class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * GET /api/requests/prepare-data
     * Préparer les données nécessaires pour créer une demande
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
     * POST /api/requests/search-address
     * Rechercher une adresse (intégration avec Google Maps API)
     */
    public function searchAddress(HttpRequest $request)
    {
        $request->validate([
            'query' => 'required|string|min:3'
        ]);

        // TODO: Intégrer Google Maps Geocoding API
        // Pour l'instant, retour mock
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
     * POST /api/requests
     * Créer ou mettre à jour une demande
     */
    public function saveRequest(SaveRequestRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            if ($request->has('id') && $request->id) {
                // Mise à jour
                $requestModel = $this->requestService->updateRequest($request->id, $data, $user);
                $message = 'Demande mise à jour avec succès';
            } else {
                // Création
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
     * PUT /api/requests/{id}/status
     * Changer le statut d'une demande
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
     * GET /api/requests
     * Charger la liste des demandes avec filtres et pagination
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

            // Filtres
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

            // Si client, afficher uniquement ses demandes
            // if (!auth()->user()->hasRole(['admin', 'manager'])) {
            //     $query->where('user_id', auth()->id());
            // }

            // Pagination avec last_id
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
     * GET /api/requests/search
     * Rechercher des demandes
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

            // Appliquer les filtres actifs
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Recherche par keywords
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

            // if (!auth()->user()->hasRole(['admin', 'manager'])) {
            //     $query->where('user_id', auth()->id());
            // }

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
     * GET /api/requests/{id}
     * Voir les détails d'une demande
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

            // Vérifier les permissions
            // if (!auth()->user()->hasRole(['admin', 'manager']) && $request->user_id !== auth()->id()){
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Vous n\'êtes pas autorisé à voir cette demande'
            //     ], 403);
            // }

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

    /**
     * DELETE /api/requests/{id}
     * Supprimer une demande (soft delete)
     */
    public function destroy($id)
    {
        try {
            $request = Request::findOrFail($id);

            // Vérifier les permissions
            // if (!auth()->user()->hasRole(['admin', 'manager'])) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Vous n\'êtes pas autorisé à supprimer cette demande'
            //     ], 403);
            // }

            // Ne pas supprimer si déjà validée ou en cours
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