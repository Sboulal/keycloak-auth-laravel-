<?php
// app/Services/RequestService.php
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
            // Calculer le montant
            $amount = $this->calculateAmount($data);

            // Créer la demande
            $request = Request::create([
                'user_id' => $user->id,
                'center_id' => $data['center_id'],
                'delivery_type_id' => $data['delivery_type_id'],
                'sender_full_name' => $data['sender_full_name'],
                'sender_phone' => $data['sender_phone'],
                'sender_email' => $data['sender_email'] ?? null,
                'sender_address' => $data['sender_address'],
                'sender_latitude' => $data['sender_latitude'] ?? null,
                'sender_longitude' => $data['sender_longitude'] ?? null,
                'sender_city_id' => $data['sender_city_id'],
                'recipient_full_name' => $data['recipient_full_name'],
                'recipient_phone' => $data['recipient_phone'],
                'recipient_address' => $data['recipient_address'],
                'recipient_latitude' => $data['recipient_latitude'] ?? null,
                'recipient_longitude' => $data['recipient_longitude'] ?? null,
                'recipient_city_id' => $data['recipient_city_id'],
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'source' => $data['source'] ?? 'online',
                'status' => $user->hasRole('admin') ? 'accepted' : 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            // Créer le colis
            $package = Package::create([
                'request_id' => $request->id,
                'weight' => $data['package']['weight'],
                'length' => $data['package']['length'] ?? null,
                'width' => $data['package']['width'] ?? null,
                'height' => $data['package']['height'] ?? null,
                'content_type' => $data['package']['content_type'],
                'description' => $data['package']['description'] ?? null,
                'declared_value' => $data['package']['declared_value'] ?? null,
            ]);

            // Générer QR Code
            // $qrCode = $this->qrCodeService->generateBase64($request);
            // $request->update(['qr_code' => $qrCode]);

            return $request->load(['package', 'center', 'deliveryType']);
        });
    }

    public function updateRequest($requestId, array $data, $user)
    {
        return DB::transaction(function () use ($requestId, $data, $user) {
            $request = Request::findOrFail($requestId);

            // Recalculer le montant si nécessaire
            if (isset($data['delivery_type_id']) || isset($data['package']['weight'])) {
                $data['amount'] = $this->calculateAmount(array_merge(
                    $request->toArray(),
                    $data
                ));
            }

            // Mettre à jour la demande
            $request->update(array_filter($data, fn($key) => !is_array($data[$key]), ARRAY_FILTER_USE_KEY));

            // Mettre à jour le colis
            if (isset($data['package'])) {
                $request->package->update($data['package']);
            }

            return $request->load(['package', 'center', 'deliveryType']);
        });
    }

    protected function calculateAmount(array $data)
    {
        $pricing = RegionTypePricing::where('city_id', $data['recipient_city_id'])
            ->where('delivery_type_id', $data['delivery_type_id'])
            ->firstOrFail();

        $weight = $data['package']['weight'] ?? 0;
        
        return $pricing->base_price + ($weight * $pricing->price_per_kg);
    }

    public function changeStatus($requestId, $status, $user)
    {
        $request = Request::findOrFail($requestId);
        
        $request->update([
            'status' => $status,
            'validated_at' => $status === 'accepted' ? now() : null,
            'validated_by' => $status === 'accepted' ? $user->id : null,
        ]);

        return $request;
    }
}