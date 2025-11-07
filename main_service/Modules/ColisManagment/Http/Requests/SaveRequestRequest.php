<?php

namespace Modules\ColisManagment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            // Informations générales
            'center_id' => 'required|exists:centers,id',
            'delivery_type_id' => 'required|exists:delivery_types,id',
            
            // Expéditeur
            'sender_full_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'sender_address' => 'required|string|max:500',
            'sender_latitude' => 'nullable|numeric|between:-90,90',
            'sender_longitude' => 'nullable|numeric|between:-180,180',
            'sender_city_id' => 'required|exists:city,id',
            
            // Destinataire
            'recipient_full_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string|max:500',
            'recipient_latitude' => 'nullable|numeric|between:-90,90',
            'recipient_longitude' => 'nullable|numeric|between:-180,180',
            'recipient_city_id' => 'required|exists:city,id',
            
            // Paiement
            'payment_method' => 'required|in:online,cash',
            
            // Colis
            'package.weight' => 'required|numeric|min:0.1|max:1000',
            'package.length' => 'nullable|numeric|min:1|max:500',
            'package.width' => 'nullable|numeric|min:1|max:500',
            'package.height' => 'nullable|numeric|min:1|max:500',
            'package.content_type' => 'required|in:fragile,standard,documents,electronics,food,others',
            'package.description' => 'nullable|string|max:1000',
            'package.declared_value' => 'nullable|numeric|min:0|max:1000000',
            
            // Optionnel
            'notes' => 'nullable|string|max:1000',
            'source' => 'nullable|in:online,in_person',
        ];

        // Si mise à jour
        if ($this->has('id') && $this->id) {
            $rules['id'] = 'required|exists:requests,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'center_id.required' => 'Le centre est obligatoire',
            'center_id.exists' => 'Le centre sélectionné n\'existe pas',
            
            'delivery_type_id.required' => 'Le type de livraison est obligatoire',
            'delivery_type_id.exists' => 'Le type de livraison sélectionné n\'existe pas',
            
            'sender_full_name.required' => 'Le nom complet de l\'expéditeur est obligatoire',
            'sender_phone.required' => 'Le téléphone de l\'expéditeur est obligatoire',
            'sender_address.required' => 'L\'adresse de l\'expéditeur est obligatoire',
            'sender_city_id.required' => 'La ville de l\'expéditeur est obligatoire',
            'sender_city_id.exists' => 'La ville de l\'expéditeur n\'existe pas',
            
            'recipient_full_name.required' => 'Le nom complet du destinataire est obligatoire',
            'recipient_phone.required' => 'Le téléphone du destinataire est obligatoire',
            'recipient_address.required' => 'L\'adresse du destinataire est obligatoire',
            'recipient_city_id.required' => 'La ville du destinataire est obligatoire',
            'recipient_city_id.exists' => 'La ville du destinataire n\'existe pas',
            
            'payment_method.required' => 'La méthode de paiement est obligatoire',
            'payment_method.in' => 'La méthode de paiement doit être: en ligne ou en espèces',
            
            'package.weight.required' => 'Le poids du colis est obligatoire',
            'package.weight.min' => 'Le poids du colis doit être au minimum 0.1 kg',
            'package.weight.max' => 'Le poids du colis ne peut pas dépasser 1000 kg',
            
            'package.content_type.required' => 'Le type de contenu est obligatoire',
            'package.content_type.in' => 'Le type de contenu sélectionné n\'est pas valide',
        ];
    }
}