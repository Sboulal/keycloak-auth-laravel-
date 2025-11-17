<?php

namespace Modules\ColisManagment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
     public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'amount' => (float) $this->amount,
            'source' => $this->source,
            
            // Expéditeur
            'sender' => [
                'full_name' => $this->sender_full_name,
                'phone' => $this->sender_phone,
                'email' => $this->sender_email,
                'address' => $this->sender_address,
                'city' => $this->whenLoaded('senderCity', function () {
                    return $this->senderCity->name;
                }),
                'coordinates' => [
                    'latitude' => $this->sender_latitude,
                    'longitude' => $this->sender_longitude,
                ],
            ],
            
            // Destinataire
            'recipient' => [
                'full_name' => $this->recipient_full_name,
                'phone' => $this->recipient_phone,
                'address' => $this->recipient_address,
                'city' => $this->whenLoaded('recipientCity', function () {
                    return $this->recipientCity->name;
                }),
                'coordinates' => [
                    'latitude' => $this->recipient_latitude,
                    'longitude' => $this->recipient_longitude,
                ],
            ],
            
            // Relations
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            'center' => $this->whenLoaded('center', function () {
                return [
                    'id' => $this->center->id,
                    'name' => $this->center->name,
                    'city' => $this->center->city->name ?? null,
                ];
            }),
            
            'delivery_type' => $this->whenLoaded('deliveryType', function () {
                return [
                    'id' => $this->deliveryType->id,
                    'name' => $this->deliveryType->name,
                ];
            }),
            
            'package' => new PackageResource($this->whenLoaded('package')),
            
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'invoice_number' => $this->payment->invoice_number,
                    'status' => $this->payment->status,
                ];
            }),
            
            'qr_code' => $this->qr_code,
            'notes' => $this->notes,
            
            'validated_at' => $this->validated_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
