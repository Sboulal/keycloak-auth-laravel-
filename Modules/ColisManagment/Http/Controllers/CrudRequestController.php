<?php

namespace Modules\ColisManagment\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Routing\Controller;
use Illuminate\Http\Request as HttpRequest;
use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Services\RequestService;
use Modules\ColisManagment\Http\Requests\SaveRequestRequest;

class CrudRequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Create Request
     */
    public function store(SaveRequestRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            $requestModel = $this->requestService->createRequest($data, $user);

            return response()->json([
                'success' => true,
                'data' => $requestModel,
                'message' => 'Demande créée avec succès. Code: ' . $requestModel->code
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error creating request: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la demande: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Request
     */
    public function update(SaveRequestRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            $requestModel = $this->requestService->updateRequest($id, $data, $user);

            return response()->json([
                'success' => true,
                'data' => $requestModel,
                'message' => 'Demande mise à jour avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error updating request: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la demande: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change Request Status
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
            Log::error('Error changing status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search Requests
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
            Log::error('Error searching requests: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Request
     */
    public function destroy($id)
    {
        try {
            $request = Request::findOrFail($id);

            if (in_array($request->status, ['accepted']) && 
                $request->package && 
                $request->package->status !== 'pending') {
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
            Log::error('Error deleting request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}