<?php
// Modules/ColisManagment/Services/RequestService.php
namespace Modules\ColisManagment\Services;

use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Entities\Package;
use Modules\ColisManagment\Entities\RegionTypePricing;
use Illuminate\Support\Facades\DB;

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
                $data['sender_latitude'] ?? 0,
                $data['sender_longitude'] ?? 0,
                $data['recipient_latitude'] ?? 0,
                $data['recipient_longitude'] ?? 0
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
                'city_id' => $data['sender_city_id'], // Ville d'expédition principale
                'delivery_type_id' => $data['delivery_type_id'],
                'nom' => $senderNames['nom'],
                'prenom' => $senderNames['prenom'],
                'telephone' => $data['sender_phone'],
                'adresse' => $data['sender_address'],
                'latitude' => $data['sender_latitude'] ?? null,
                'longitude' => $data['sender_longitude'] ?? null,
                'poids' => $data['package']['weight'],
                'amount' => $amount,
                'status' => Request::STATUS_PENDING, // 0
                'payment_status' => Request::PAYMENT_UNPAID, // 0
            ]);

            // Créer le colis avec infos complètes
            $package = Package::create([
                'request_id' => $request->id,
                'code' => 'PKG-' . strtoupper(uniqid()),
                'weight' => $data['package']['weight'],
                'length' => $data['package']['length'] ?? null,
                'width' => $data['package']['width'] ?? null,
                'height' => $data['package']['height'] ?? null,
                'content_type' => $data['package']['content_type'] ?? 'standard',
                'description' => $data['package']['description'] ?? null,
                'declared_value' => $data['package']['declared_value'] ?? 0,
                // Stocker les infos du destinataire dans le package si nécessaire
                'recipient_name' => $data['recipient_full_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'recipient_address' => $data['recipient_address'] ?? null,
                'recipient_city_id' => $data['recipient_city_id'] ?? null,
                'recipient_latitude' => $data['recipient_latitude'] ?? null,
                'recipient_longitude' => $data['recipient_longitude'] ?? null,
                'distance' => $distance,
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'source' => $data['source'] ?? 'online',
            ]);

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

            // Recalculer le montant si nécessaire
            if (isset($data['delivery_type_id']) || isset($data['package']['weight'])) {
                $mergedData = array_merge($request->toArray(), $data);
                $distance = $this->calculateDistance(
                    $mergedData['latitude'] ?? 0,
                    $mergedData['longitude'] ?? 0,
                    $request->package->recipient_latitude ?? 0,
                    $request->package->recipient_longitude ?? 0
                );
                $updateData['amount'] = $this->calculateAmount($mergedData, $distance);
            }

            // Mettre à jour la demande
            if (!empty($updateData)) {
                $request->update($updateData);
            }

            // Mettre à jour le colis
            if (isset($data['package'])) {
                $packageData = $data['package'];
                
                if (isset($data['package']['weight'])) {
                    $packageData['weight'] = $data['package']['weight'];
                }
                
                $request->package->update($packageData);
            }

            // Mettre à jour les infos du destinataire dans le package
            $recipientData = [];
            if (isset($data['recipient_full_name'])) {
                $recipientData['recipient_name'] = $data['recipient_full_name'];
            }
            if (isset($data['recipient_phone'])) {
                $recipientData['recipient_phone'] = $data['recipient_phone'];
            }
            if (isset($data['recipient_address'])) {
                $recipientData['recipient_address'] = $data['recipient_address'];
            }
            if (isset($data['recipient_city_id'])) {
                $recipientData['recipient_city_id'] = $data['recipient_city_id'];
            }
            
            if (!empty($recipientData)) {
                $request->package->update($recipientData);
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

        if (!$senderPricing || !$recipientPricing) {
            throw new \Exception("Tarification non disponible pour cette combinaison ville/type de livraison");
        }

        $weight = $data['package']['weight'] ?? $data['poids'] ?? 0;

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
        
        // Mapper les statuts string vers integer
        $statusMap = [
            'pending' => Request::STATUS_PENDING,
            'validated' => Request::STATUS_VALIDATED,
            'accepted' => Request::STATUS_VALIDATED,
            'cancelled' => Request::STATUS_CANCELLED,
        ];

        $numericStatus = $statusMap[$status] ?? Request::STATUS_PENDING;
        
        $request->update([
            'status' => $numericStatus,
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