<?php

namespace Modules\ColisManagment\Services;


use Illuminate\Support\Str;
use Modules\Core\Entities\City;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Entities\Payment;
use Modules\Core\Entities\DeliveryType;
use Modules\Core\Entities\Center;
use Modules\ColisManagment\Entities\Package;
use Modules\Core\Entities\RegionTypePricing;
use Modules\Payment\Services\PaymentService;
use Modules\Core\Services\NotificationService;
use Modules\ColisManagment\Entities\Request as RequestModel;
use Symfony\Component\HttpFoundation\Request;

class RequestService
{
    protected $qrCodeService;
    protected $addressService;
    protected $paymentService;
    protected $notificationService;

    // public function __construct(
    //     QRCodeService $qrCodeService,
    //     AddressService $addressService,
    //     PaymentService $paymentService,
    //     NotificationService $notificationService
    // ) {
    //     $this->qrCodeService = $qrCodeService;
    //     $this->addressService = $addressService;
    //     $this->paymentService = $paymentService;
    //     $this->notificationService = $notificationService;
    // }
    public function __construct()
    {
       
    }

    /**
     * Préparer les données pour créer une demande
     */
    public function prepareRequestData($user): array
    {
        // Récupérer toutes les villes actives
        $cities = City::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code_city']);

        // Récupérer les centres groupés par ville
        $centers = Center::where('is_active', true)
            ->get(['id', 'name', 'address', 'city_id'])
            ->groupBy('city_id');

        // Récupérer les types de livraison avec leurs tarifs
        $deliveryTypes = DeliveryType::where('is_active', true)
            ->with(['regionPricing' => function($query) {
                $query->where('is_active', true)
                    ->with('city:id,name');
            }])
            ->get();

        // Formatter les prix par ville pour chaque type
        $formattedDeliveryTypes = $deliveryTypes->map(function($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'code_dt' => $type->code_dt,
                'description' => $type->description,
                'pricing_by_city' => $type->regionPricing->mapWithKeys(function($pricing) {
                    return [
                        $pricing->city_id => [
                            'base_price' => $pricing->base_price,
                            'price_per_kg' => $pricing->price_per_kg,
                            'city_name' => $pricing->city->name
                        ]
                    ];
                })
            ];
        });

        // Données utilisateur
        $userData = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => $user->address,
            'address_latitude' => $user->address_latitude,
            'address_longitude' => $user->address_longitude,
            'city' => $user->city,
            'postal_code' => $user->postal_code
        ];

        return [
            'cities' => $cities,
            'centers' => $centers,
            'delivery_types' => $formattedDeliveryTypes,
            'user_data' => $userData
        ];
    }

    /**
     * Rechercher une adresse avec géocodage
     */
    public function searchAddress(string $address): array
    {
        return $this->addressService->geocodeAddress($address);
    }

    /**
     * Sauvegarder une demande (nouvelle ou mise à jour)
     */
    public function saveRequest(array $data, $user): array
    {
        return DB::transaction(function () use ($data, $user) {
            $isUpdate = !empty($data['request_id']);
            $isAdmin = $user->hasRole(['admin', 'manager']);

            // 1. Mettre à jour les infos utilisateur si nécessaire
            if (isset($data['user'])) {
                $user->update([
                    'phone' => $data['user']['phone'] ?? $user->phone,
                    'address' => $data['user']['address'] ?? $user->address,
                    'address_latitude' => $data['user']['address_latitude'] ?? $user->address_latitude,
                    'address_longitude' => $data['user']['address_longitude'] ?? $user->address_longitude,
                    'city' => $data['user']['city'] ?? $user->city,
                ]);
            }

            // 2. Calculer le montant basé sur le poids et le type de livraison
            $pricing = RegionTypePricing::where('delivery_type_id', $data['request']['delivery_type_id'])
                ->whereHas('city', function($query) use ($data) {
                    $receiverCity = $this->extractCityFromAddress($data['request']['receiver_address']);
                    $query->where('name', 'like', "%{$receiverCity}%");
                })
                ->first();

            $weight = floatval($data['package']['weight']);
            $amount = $pricing 
                ? floatval($pricing->base_price) + ($weight * floatval($pricing->price_per_kg))
                : 0;

            // 3. Créer ou mettre à jour la demande
            $requestData = [
                'code_request' => $isUpdate ? null : 'D-' . Str::uuid(),
                'user_id' => $user->id,
                'center_id' => $data['request']['center_id'],
                'receiver_address' => $data['request']['receiver_address'],
                'receiver_latitude' => $data['request']['receiver_latitude'],
                'receiver_longitude' => $data['request']['receiver_longitude'],
                'receiver_phone' => $data['request']['receiver_phone'],
                'delivery_type_id' => $data['request']['delivery_type_id'],
                'estimated_time' => $data['request']['estimated_time'] ?? null,
                'amount' => number_format($amount, 2, '.', ''),
                'status' => $isAdmin ? 1 : 0, // Auto-validé si créé par admin
                'payment_status' => 0, // Impayé par défaut
            ];

            $request = $isUpdate 
                ? RequestModel::findOrFail($data['request_id'])->update($requestData)
                : RequestModel::create($requestData);

            // 4. Générer le QR Code
            if (!$isUpdate) {
                $qrData = [
                    'code' => $request->code_request,
                    'date' => $request->created_at->format('Y-m-d H:i:s'),
                    'amount' => $request->amount,
                    'delivery_type' => DeliveryType::find($request->delivery_type_id)->name
                ];
                
                $qrCodePath = $this->qrCodeService->generate($qrData, $request->code_request);
                $request->update(['qr_code_path' => $qrCodePath]);
            }

            // 5. Créer ou mettre à jour le colis
            $packageData = [
                'request_id' => $request->id,
                'code_pkg' => $isUpdate ? null : 'PKG-' . Str::uuid(),
                'tracking_number' => $isUpdate ? null : 'TRK' . date('YmdHis') . rand(100, 999),
                'weight' => $data['package']['weight'],
                'length' => $data['package']['length'],
                'width' => $data['package']['width'],
                'height' => $data['package']['height'],
                'content_type' => $data['package']['content_type'],
                'description' => $data['package']['description'] ?? null,
                'declared_value' => $data['package']['declared_value'] ?? null,
                'status' => 0, // En attente
            ];

            $package = $isUpdate && $request->package
                ? $request->package->update($packageData)
                : Package::create($packageData);

            // 6. Créer l'entrée de paiement
            $paymentData = [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'payment_code' => 'PAY-' . Str::uuid(),
                'method' => $data['payment']['method'],
                'amount' => $request->amount,
                'currency' => 'MAD',
                'status' => 0, // En attente
            ];

            $payment = Payment::create($paymentData);
            $request->update(['payment_id' => $payment->id]);

            // 7. Si paiement en ligne, générer l'URL de paiement
            if ($data['payment']['method'] == 0) {
                $paymentUrl = $this->paymentService->generatePaymentUrl($payment);
                $payment->update([
                    'payment_details' => json_encode(['payment_url' => $paymentUrl])
                ]);
            }

            // 8. Envoyer les notifications
            $this->notificationService->sendRequestCreatedNotification($request, $user);

            return [
                'request' => $request->fresh(['package', 'payment', 'deliveryType']),
                'package' => $package,
                'payment' => $payment
            ];
        });
    }

    /**
     * Changer le statut d'une demande
     */
    public function changeRequestStatus(int $requestId, int $status): RequestModel
    {
        $request = RequestModel::findOrFail($requestId);
        $request->update(['status' => $status]);

        // Notifier l'utilisateur du changement
        $this->notificationService->sendStatusChangeNotification($request);

        return $request->fresh();
    }

    /**
     * Appliquer le paiement
     */
    public function applyPayment(int $requestId, array $paymentData): array
    {
        return DB::transaction(function () use ($requestId, $paymentData) {
            $request = RequestModel::with('payment')->findOrFail($requestId);
            
            // Mettre à jour le paiement
            $request->payment->update([
                'method' => $paymentData['method'],
                'amount' => $paymentData['amount'],
                'status' => 1, // Completed
                'payment_details' => json_encode($paymentData['payment_details'] ?? [])
            ]);

            // Mettre à jour le statut de paiement de la demande
            $request->update([
                'payment_status' => 1, // Payé
                'status' => 1 // Auto-valider
            ]);

            // Générer la facture
            $invoice = $this->paymentService->generateInvoice($request->payment);

            // Envoyer par email
            $this->notificationService->sendInvoiceEmail($request->user, $invoice);

            return [
                'payment' => $request->payment->fresh(),
                'request' => $request->fresh()
            ];
        });
    }

    /**
     * Changer le statut de paiement
     */
    public function changePaymentStatus(int $requestId, int $status): RequestModel
    {
        $request = RequestModel::findOrFail($requestId);
        $request->update(['payment_status' => $status]);

        return $request->fresh();
    }

    /**
     * Charger la liste des demandes avec filtres
     */
    public function loadRequestsList(array $filters, $user): array
    {
        $query = RequestModel::with(['user', 'center.city', 'package', 'payment', 'deliveryType'])
            ->orderBy('created_at', 'DESC');

        // Filtres utilisateur vs admin
        if (!$user->hasRole(['admin', 'manager'])) {
            $query->where('user_id', $user->id);
        }

        // Appliquer les filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['center_id'])) {
            $query->where('center_id', $filters['center_id']);
        }

        // Pagination avec last_id
        if (isset($filters['last_id'])) {
            $query->where('id', '<', $filters['last_id']);
        }

        $results = $query->take($filters['per_page'])->get();
        $moreToLoad = count($results) === $filters['per_page'];

        return [
            'data' => $results,
            'more_to_load' => $moreToLoad
        ];
    }

    /**
     * Rechercher des demandes
     */
    public function searchRequests(array $keywords, array $filters, $user)
    {
        $query = RequestModel::with(['user', 'center.city', 'package', 'payment'])
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('code_request', 'like', "%{$keyword}%")
                      ->orWhereHas('user', function($userQuery) use ($keyword) {
                          $userQuery->where('first_name', 'like', "%{$keyword}%")
                                    ->orWhere('last_name', 'like', "%{$keyword}%");
                      });
                }
            });

        // Filtres utilisateur vs admin
        if (!$user->hasRole(['admin', 'manager'])) {
            $query->where('user_id', $user->id);
        }

        // Appliquer les filtres supplémentaires
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query->where($key, $value);
            }
        }

        return $query->orderBy('created_at', 'DESC')->get();
    }

    /**
     * Obtenir les détails d'une demande
     */
    public function getRequestDetails(int $requestId): RequestModel
    {
        return RequestModel::with([
            'user',
            'center.city',
            'package.deliveries.driver',
            'payment',
            'deliveryType'
        ])->findOrFail($requestId);
    }

    /**
     * Extraire la ville depuis une adresse
     */
    private function extractCityFromAddress(string $address): string
    {
        // Simple extraction - peut être amélioré
        $cities = ['Casablanca', 'Rabat', 'Fès', 'Marrakech', 'Tangier', 'Agadir', 'Meknès', 'Oujda', 'Kenitra', 'Mohammedia'];
        
        foreach ($cities as $city) {
            if (stripos($address, $city) !== false) {
                return $city;
            }
        }
        
        return 'Casablanca'; // Par défaut
    }
}