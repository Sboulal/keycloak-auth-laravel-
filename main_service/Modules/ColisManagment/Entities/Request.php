<?php

namespace Modules\ColisManagment\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'center_id',
        'city_id',
        'delivery_type_id',
        'nom',
        'prenom',
        'telephone',
        'adresse',
        'latitude',
        'longitude',
        'poids',
        'amount',
        'status',          // 0 pending | 1 validée | 2 annulée
        'payment_status',  // 0 impayée | 1 payée | 2 remboursée
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'amount' => 'float',
        'poids' => 'float',
        'status' => 'integer',
        'payment_status' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    // demande appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(\Modules\UserManagment\Entities\User::class);
    }

    // centre
    public function center()
    {
        return $this->belongsTo(\Modules\Core\Entities\Center::class);
    }

    // ville
    public function city()
    {
        return $this->belongsTo(\Modules\Core\Entities\City::class);
    }

    // type livraison
    public function deliveryType()
    {
        return $this->belongsTo(\Modules\Core\Entities\DeliveryType::class);
    }

    // le colis attaché à la demande
    public function package()
    {
        return $this->hasOne(\Modules\ColisManagment\Entities\Package::class);
    }

    // paiement lié à la demande
    public function payment()
    {
        return $this->hasOne(\Modules\Payment\Entities\Payment::class);
    }
}
