<?php

namespace Modules\ColisManagment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequestListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'pickup_address' => $this->pickup_address,
            'delivery_address' => $this->delivery_address,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'price' => $this->price,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s')
        ];
    }
}
