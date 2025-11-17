<?php
// Modules/ColisManagment/Services/RequestService.php
namespace Modules\ColisManagment\Services;

use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Entities\Package;
use Modules\ColisManagment\Entities\RegionTypePricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestService
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function createRequest(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            // Générer le code unique
            $code = 'REQ-' . strtoupper(uniqid());

            // Calculer la distance entre les deux villes
            $distance = $this->calculateDistance(
                $data['sender_latitude'] ?? null,
                $data['sender_longitude'] ?? null,
                $data['recipient_latitude'] ?? null,
                $data['recipient_longitude'] ?? null
            );

            // Calculer le montant avec distance
            $amount = $this->calculateAmount($data, $distance);

            // Extraire nom et prénom de sender_full_name
            $senderNames = $this->splitFullName($data['sender_full_name']);

            // Créer la demande avec l'ancien schéma
            $request = Request::create([
                'code' => $code,
                'user_id' => $user->id,
                'center_id' => $data['center_id'],
                'city_id' => $data['sender_city_id'],
                'delivery_type_id' => $data['delivery_type_id'],
                'nom' => $senderNames['nom'],
                'prenom' => $senderNames['prenom'],
                'telephone' => $data['sender_phone'],
                'adresse' => $data['sender_address'],
                'latitude' => $data['sender_latitude'] ?? null,
                'longitude' => $data['sender_longitude'] ?? null,
                'poids' => $data['weight'], // FIXED: Use flat structure
                'amount' => $amount,
                'status' => Request::STATUS_PENDING,
                'payment_status' => Request::PAYMENT_UNPAID,
            ]);

            // Préparer les données du package
            $packageData = [
                'request_id' => $request->id,
                'code' => 'PKG-' . strtoupper(uniqid()),
                'weight' => $data['weight'],
                'description' => $data['description'] ?? null,
                'declared_value' => $data['declared_value'] ?? 0,
            ];

            // Ajouter les champs optionnels seulement s'ils existent dans la table
            // FIXED: Only add fields that exist in your packages table
            $optionalFields = [
                'length', 'width', 'height', 'content_type',
                'recipient_name', 'recipient_phone', 'recipient_address',
                'recipient_city_id', 'recipient_latitude', 'recipient_longitude',
                'distance', 'notes', 'payment_method', 'source'
            ];

            foreach ($optionalFields as $field) {
                // Map from request data to package field names
                $sourceField = match($field) {
                    'recipient_name' => 'recipient_full_name',
                    default => $field
                };
                
                if (isset($data[$sourceField])) {
                    $packageData[$field] = $data[$sourceField];
                }
            }

            // Set defaults for certain fields
            $packageData['distance'] = $distance;
            $packageData['payment_method'] = $data['payment_method'] ?? 'cash';
            $packageData['source'] = $data['source'] ?? 'online';

            // Créer le colis
            $package = Package::create($packageData);

            // Générer QR Code (optionnel)
            // $qrCode = $this->qrCodeService->generateBase64($request);
            // $request->update(['qr_code' => $qrCode]);

            return $request->load(['package', 'center', 'city', 'deliveryType', 'user']);
        });
    }

    public function updateRequest($requestId, array $data, $user)
    {
        return DB::transaction(function () use ($requestId, $data, $user) {
            $request = Request::findOrFail($requestId);

            // Préparer les données de mise à jour
            $updateData = [];

            if (isset($data['center_id'])) {
                $updateData['center_id'] = $data['center_id'];
            }

            if (isset($data['delivery_type_id'])) {
                $updateData['delivery_type_id'] = $data['delivery_type_id'];
            }

            if (isset($data['sender_full_name'])) {
                $names = $this->splitFullName($data['sender_full_name']);
                $updateData['nom'] = $names['nom'];
                $updateData['prenom'] = $names['prenom'];
            }

            if (isset($data['sender_phone'])) {
                $updateData['telephone'] = $data['sender_phone'];
            }

            if (isset($data['sender_address'])) {
                $updateData['adresse'] = $data['sender_address'];
            }

            if (isset($data['sender_latitude'])) {
                $updateData['latitude'] = $data['sender_latitude'];
            }

            if (isset($data['sender_longitude'])) {
                $updateData['longitude'] = $data['sender_longitude'];
            }

            if (isset($data['sender_city_id'])) {
                $updateData['city_id'] = $data['sender_city_id'];
            }

            // FIXED: Use flat weight structure
            if (isset($data['weight'])) {
                $updateData['poids'] = $data['weight'];
            }

            // Recalculer le montant si nécessaire
            if (isset($data['delivery_type_id']) || isset($data['weight'])) {
                $mergedData = array_merge($request->toArray(), $data);
                $distance = $this->calculateDistance(
                    $mergedData['latitude'] ?? null,
                    $mergedData['longitude'] ?? null,
                    $request->package->recipient_latitude ?? null,
                    $request->package->recipient_longitude ?? null
                );
                $updateData['amount'] = $this->calculateAmount($mergedData, $distance);
            }

            // Mettre à jour la demande
            if (!empty($updateData)) {
                $request->update($updateData);
            }

            // Mettre à jour le colis
            $packageData = [];
            
            if (isset($data['weight'])) {
                $packageData['weight'] = $data['weight'];
            }
            
            if (isset($data['description'])) {
                $packageData['description'] = $data['description'];
            }
            
            if (isset($data['declared_value'])) {
                $packageData['declared_value'] = $data['declared_value'];
            }

            // Mettre à jour les infos du destinataire dans le package
            if (isset($data['recipient_full_name'])) {
                $packageData['recipient_name'] = $data['recipient_full_name'];
            }
            if (isset($data['recipient_phone'])) {
                $packageData['recipient_phone'] = $data['recipient_phone'];
            }
            if (isset($data['recipient_address'])) {
                $packageData['recipient_address'] = $data['recipient_address'];
            }
            if (isset($data['recipient_city_id'])) {
                $packageData['recipient_city_id'] = $data['recipient_city_id'];
            }
            
            if (!empty($packageData)) {
                $request->package->update($packageData);
            }

            return $request->load(['package', 'center', 'city', 'deliveryType']);
        });
    }

    protected function calculateAmount(array $data, $distance = 0)
    {
        // Récupérer les tarifs pour la ville d'expédition et de destination
        $senderPricing = RegionTypePricing::where('city_id', $data['sender_city_id'])
            ->where('delivery_type_id', $data['delivery_type_id'])
            ->where('is_active', true)
            ->first();

        $recipientPricing = RegionTypePricing::where('city_id', $data['recipient_city_id'])
            ->where('delivery_type_id', $data['delivery_type_id'])
            ->where('is_active', true)
            ->first();

        // FIXED: Return 422 validation error instead of 500
        if (!$senderPricing || !$recipientPricing) {
            throw ValidationException::withMessages([
                'pricing' => ['Tarification non disponible pour cette combinaison ville/type de livraison']
            ]);
        }

        // FIXED: Use flat weight structure
        $weight = $data['weight'] ?? $data['poids'] ?? 0;

        // Calculer le prix moyen entre les deux villes
        $basePrice = ($senderPricing->base_price + $recipientPricing->base_price) / 2;
        $pricePerKg = ($senderPricing->price_per_kg + $recipientPricing->price_per_kg) / 2;
        $pricePerKm = ($senderPricing->price_per_km + $recipientPricing->price_per_km) / 2;

        // Calcul: Prix de base + (poids × prix/kg) + (distance × prix/km)
        $amount = $basePrice + ($weight * $pricePerKg) + ($distance * $pricePerKm);

        return round($amount, 2);
    }

    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // FIXED: Return 0 if any coordinate is null or 0
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return 0;
        }

        $earthRadius = 6371; // Rayon de la Terre en km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    protected function splitFullName($fullName)
    {
        $parts = explode(' ', trim($fullName), 2);
        
        return [
            'prenom' => $parts[0] ?? '',
            'nom' => $parts[1] ?? ''
        ];
    }

    public function changeStatus($requestId, $status, $user)
    {
        $request = Request::findOrFail($requestId);
        
        // FIXED: More consistent status mapping
        $statusMap = [
            'pending' => Request::STATUS_PENDING,
            'validated' => Request::STATUS_VALIDATED,
            'accepted' => Request::STATUS_VALIDATED, // Keep this if 'accepted' means validated
            'rejected' => Request::STATUS_REJECTED ?? 2, // Add constant if missing
            'cancelled' => Request::STATUS_CANCELLED,
        ];

        $numericStatus = $statusMap[$status] ?? Request::STATUS_PENDING;
        
        $request->update([
            'status' => $numericStatus,
            'validated_by' => ($status === 'accepted' || $status === 'validated') ? $user->id : null,
            'validated_at' => ($status === 'accepted' || $status === 'validated') ? now() : null,
        ]);

        return $request;
    }

    public function changePaymentStatus($requestId, $paymentStatus)
    {
        $request = Request::findOrFail($requestId);
        
        $paymentStatusMap = [
            'unpaid' => Request::PAYMENT_UNPAID,
            'paid' => Request::PAYMENT_PAID,
            'refunded' => Request::PAYMENT_REFUNDED,
        ];

        $numericPaymentStatus = $paymentStatusMap[$paymentStatus] ?? Request::PAYMENT_UNPAID;
        
        $request->update([
            'payment_status' => $numericPaymentStatus,
        ]);

        return $request;
    }
}