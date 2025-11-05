<?php

namespace Modules\ColisManagment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SaveRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            // ID pour update
            'id' => 'nullable|exists:requests,id',
            
            // Informations générales
            'center_id' => 'required|exists:centers,id',
            'delivery_type_id' => 'required|exists:delivery_types,id',
            
            // Expéditeur
            'sender_full_name' => 'required|string|max:255',
            'sender_phone' => [
                'required',
                'string',
                'regex:/^(06|07)[0-9]{8}$/' // Format marocain
            ],
            'sender_email' => 'nullable|email|max:255',
            'sender_address' => 'required|string|max:500',
            'sender_latitude' => 'nullable|numeric|between:-90,90',
            'sender_longitude' => 'nullable|numeric|between:-180,180',
            'sender_city_id' => 'required|exists:cities,id',
            
            // Destinataire
            'recipient_full_name' => 'required|string|max:255',
            'recipient_phone' => [
                'required',
                'string',
                'regex:/^(06|07)[0-9]{8}$/'
            ],
            'recipient_address' => 'required|string|max:500',
            'recipient_latitude' => 'nullable|numeric|between:-90,90',
            'recipient_longitude' => 'nullable|numeric|between:-180,180',
            'recipient_city_id' => 'required|exists:cities,id',
            
            // Paiement
            'payment_method' => 'required|in:online,cash',
            
            // Colis
            'package' => 'required|array',
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

        return $rules;
    }

    public function messages()
    {
        return [
            // Général
            'center_id.required' => 'Le centre de dépôt est obligatoire',
            'center_id.exists' => 'Le centre sélectionné n\'existe pas',
            
            'delivery_type_id.required' => 'Le type de livraison est obligatoire',
            'delivery_type_id.exists' => 'Le type de livraison sélectionné n\'existe pas',
            
            // Expéditeur
            'sender_full_name.required' => 'Le nom complet de l\'expéditeur est obligatoire',
            'sender_full_name.max' => 'Le nom ne peut pas dépasser 255 caractères',
            
            'sender_phone.required' => 'Le numéro de téléphone de l\'expéditeur est obligatoire',
            'sender_phone.regex' => 'Le numéro de téléphone doit être au format marocain (06XXXXXXXX ou 07XXXXXXXX)',
            
            'sender_email.email' => 'L\'email de l\'expéditeur n\'est pas valide',
            
            'sender_address.required' => 'L\'adresse de l\'expéditeur est obligatoire',
            'sender_address.max' => 'L\'adresse ne peut pas dépasser 500 caractères',
            
            'sender_city_id.required' => 'La ville de l\'expéditeur est obligatoire',
            'sender_city_id.exists' => 'La ville sélectionnée n\'existe pas',
            
            // Destinataire
            'recipient_full_name.required' => 'Le nom complet du destinataire est obligatoire',
            
            'recipient_phone.required' => 'Le numéro de téléphone du destinataire est obligatoire',
            'recipient_phone.regex' => 'Le numéro de téléphone doit être au format marocain',
            
            'recipient_address.required' => 'L\'adresse du destinataire est obligatoire',
            
            'recipient_city_id.required' => 'La ville du destinataire est obligatoire',
            'recipient_city_id.exists' => 'La ville sélectionnée n\'existe pas',
            
            // Paiement
            'payment_method.required' => 'La méthode de paiement est obligatoire',
            'payment_method.in' => 'La méthode de paiement doit être "online" ou "cash"',
            
            // Colis
            'package.required' => 'Les informations du colis sont obligatoires',
            
            'package.weight.required' => 'Le poids du colis est obligatoire',
            'package.weight.numeric' => 'Le poids doit être un nombre',
            'package.weight.min' => 'Le poids minimum est de 0.1 kg',
            'package.weight.max' => 'Le poids maximum est de 1000 kg',
            
            'package.content_type.required' => 'Le type de contenu est obligatoire',
            'package.content_type.in' => 'Le type de contenu sélectionné n\'est pas valide',
            
            'package.description.max' => 'La description ne peut pas dépasser 1000 caractères',
            
            'package.declared_value.numeric' => 'La valeur déclarée doit être un nombre',
            'package.declared_value.max' => 'La valeur déclarée ne peut pas dépasser 1 000 000 MAD',
            
            // Optionnel
            'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères',
            'source.in' => 'La source doit être "online" ou "in_person"',
        ];
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes()
    {
        return [
            'sender_full_name' => 'nom de l\'expéditeur',
            'sender_phone' => 'téléphone de l\'expéditeur',
            'sender_email' => 'email de l\'expéditeur',
            'sender_address' => 'adresse de l\'expéditeur',
            'sender_city_id' => 'ville de l\'expéditeur',
            
            'recipient_full_name' => 'nom du destinataire',
            'recipient_phone' => 'téléphone du destinataire',
            'recipient_address' => 'adresse du destinataire',
            'recipient_city_id' => 'ville du destinataire',
            
            'payment_method' => 'méthode de paiement',
            
            'package.weight' => 'poids du colis',
            'package.content_type' => 'type de contenu',
            'package.description' => 'description du colis',
            'package.declared_value' => 'valeur déclarée',
        ];
    }
}

class ChangeStatusRequest extends FormRequest
{
    public function authorize()
    {
        // Seul admin/manager peut changer le statut
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|in:pending,accepted,rejected,cancelled'
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le statut est obligatoire',
            'status.in' => 'Le statut doit être: pending, accepted, rejected ou cancelled'
        ];
    }
}

class SearchAddressRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'query' => 'required|string|min:3|max:255'
        ];
    }

    public function messages()
    {
        return [
            'query.required' => 'La recherche d\'adresse est obligatoire',
            'query.min' => 'La recherche doit contenir au moins 3 caractères',
            'query.max' => 'La recherche ne peut pas dépasser 255 caractères'
        ];
    }
}