<?php

namespace Modules\ColisManagment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Info Client
            'user_id' => 'required|exists:users,id',
            'center_id' => 'required|exists:centers,id',
            
            // Info Destinataire
            'receiver_address' => 'required|string|max:255',
            'receiver_latitude' => 'nullable|numeric|between:-90,90',
            'receiver_longitude' => 'nullable|numeric|between:-180,180',
            'receiver_phone' => 'required|string|max:20',
            
            // Info Paiement
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_id' => 'nullable|exists:payments,id',
            'amount' => 'required|numeric|min:0',
            
            // Type Livraison
            'delivery_type_id' => 'required|exists:delivery_types,id',
            
            // Statut
            'status' => 'nullable|boolean',
            
            // QR Code
            'qr_code_path' => 'nullable|string|max:255',
            
            // Info Colis (Package)
            'package.weight' => 'required|numeric|min:0',
            'package.length' => 'nullable|numeric|min:0',
            'package.width' => 'nullable|numeric|min:0',
            'package.height' => 'nullable|numeric|min:0',
            'package.content_type' => 'required|string|in:fragile,electronics,documents,clothing,food,other',
            'package.description' => 'nullable|string|max:500',
            'package.declared_value' => 'required|numeric|min:0',
            'package.code_pkg' => 'nullable|string|max:50|unique:packages,code_pkg',
            'package.tracking_number' => 'nullable|string|max:100|unique:packages,tracking_number',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'client',
            'center_id' => 'centre',
            'receiver_address' => 'adresse du destinataire',
            'receiver_phone' => 'téléphone du destinataire',
            'payment_status' => 'statut de paiement',
            'amount' => 'montant',
            'delivery_type_id' => 'type de livraison',
            'package.weight' => 'poids',
            'package.content_type' => 'type de contenu',
            'package.declared_value' => 'valeur déclarée',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Le client est obligatoire.',
            'center_id.required' => 'Le centre est obligatoire.',
            'receiver_address.required' => 'L\'adresse du destinataire est obligatoire.',
            'receiver_phone.required' => 'Le téléphone du destinataire est obligatoire.',
            'amount.required' => 'Le montant est obligatoire.',
            'package.weight.required' => 'Le poids du colis est obligatoire.',
            'package.content_type.required' => 'Le type de contenu est obligatoire.',
            'package.declared_value.required' => 'La valeur déclarée est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Générer code demande
        if (!$this->has('code_request')) {
            $this->merge([
                'code_request' => 'REQ-' . strtoupper(uniqid()),
            ]);
        }

        // Générer tracking number et code colis
        if (!$this->has('package.tracking_number')) {
            $this->merge([
                'package' => array_merge($this->input('package', []), [
                    'tracking_number' => 'TRK-' . strtoupper(uniqid()),
                    'code_pkg' => 'PKG-' . strtoupper(uniqid()),
                ])
            ]);
        }
    }
}