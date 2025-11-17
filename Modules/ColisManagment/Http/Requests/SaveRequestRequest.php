<?php

namespace Modules\ColisManagment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            // Request fields
            'center_id' => 'required|exists:centers,id',
            'delivery_type_id' => 'required|exists:delivery_types,id',
            'sender_city_id' => 'required|exists:city,id',
            'recipient_city_id' => 'required|exists:city,id',
            
            // Sender info
            'sender_full_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string|max:500',
            'sender_latitude' => 'nullable|numeric|between:-90,90',
            'sender_longitude' => 'nullable|numeric|between:-180,180',
            
            // Recipient info
            'recipient_full_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string|max:500',
            'recipient_latitude' => 'nullable|numeric|between:-90,90',
            'recipient_longitude' => 'nullable|numeric|between:-180,180',
            
            // Package info - FLAT STRUCTURE (not nested)
            'weight' => 'required|numeric|min:0.1|max:1000',
            'content_type' => 'required|string|in:documents,electronics,clothing,food,fragile,other',
            'description' => 'nullable|string|max:1000',
            'declared_value' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            
            // Optional fields
            'payment_method' => 'nullable|string|in:cash,card,online,cod',
            'source' => 'nullable|string|in:web,mobile,api,admin',
            'notes' => 'nullable|string|max:1000',
        ];

        // For updates, make fields optional if not provided
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            foreach ($rules as $key => $rule) {
                if (strpos($rule, 'required') !== false) {
                    $rules[$key] = str_replace('required', 'sometimes|required', $rule);
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages
     */
    public function messages()
    {
        return [
            'center_id.required' => 'Le centre est obligatoire',
            'center_id.exists' => 'Le centre sélectionné n\'existe pas',
            'delivery_type_id.required' => 'Le type de livraison est obligatoire',
            'delivery_type_id.exists' => 'Le type de livraison sélectionné n\'existe pas',
            'sender_city_id.required' => 'La ville d\'expédition est obligatoire',
            'sender_city_id.exists' => 'La ville d\'expédition n\'existe pas',
            'recipient_city_id.required' => 'La ville de destination est obligatoire',
            'recipient_city_id.exists' => 'La ville de destination n\'existe pas',
            
            'sender_full_name.required' => 'Le nom complet de l\'expéditeur est obligatoire',
            'sender_phone.required' => 'Le téléphone de l\'expéditeur est obligatoire',
            'sender_address.required' => 'L\'adresse de l\'expéditeur est obligatoire',
            
            'recipient_full_name.required' => 'Le nom complet du destinataire est obligatoire',
            'recipient_phone.required' => 'Le téléphone du destinataire est obligatoire',
            'recipient_address.required' => 'L\'adresse du destinataire est obligatoire',
            
            'weight.required' => 'Le poids du colis est obligatoire',
            'weight.numeric' => 'Le poids doit être un nombre',
            'weight.min' => 'Le poids minimum est 0.1 kg',
            'weight.max' => 'Le poids maximum est 1000 kg',
            
            'content_type.required' => 'Le type de contenu est obligatoire',
            'content_type.in' => 'Le type de contenu doit être: documents, electronics, clothing, food, fragile, ou other',
        ];
    }
}