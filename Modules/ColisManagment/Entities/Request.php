<?php

namespace Modules\ColisManagment\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    // Constantes pour les statuts
    const STATUS_PENDING = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CANCELLED = 2;
    
    const PAYMENT_UNPAID = 0;
    const PAYMENT_PAID = 1;
    const PAYMENT_REFUNDED = 2;
    const STATUS_REJECTED = 3;

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
        'status',
        'payment_status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'amount' => 'float',
        'poids' => 'float',
        'status' => 'integer',
        'payment_status' => 'integer',
    ];

    // Accesseurs pour avoir des labels lisibles
    public function getStatusLabelAttribute()
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_VALIDATED => 'Validée',
            self::STATUS_CANCELLED => 'Annulée',
        ][$this->status] ?? 'Inconnu';
    }

    public function getPaymentStatusLabelAttribute()
    {
        return [
            self::PAYMENT_UNPAID => 'Impayée',
            self::PAYMENT_PAID => 'Payée',
            self::PAYMENT_REFUNDED => 'Remboursée',
        ][$this->payment_status] ?? 'Inconnu';
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(\Modules\UserManagment\Entities\User::class);
    }

    public function center()
    {
        return $this->belongsTo(\Modules\Core\Entities\Center::class);
    }

    public function city()
    {
        return $this->belongsTo(\Modules\Core\Entities\City::class);
    }

    public function deliveryType()
    {
        return $this->belongsTo(\Modules\ColisManagment\Entities\DeliveryType::class);
    }

    public function package()
    {
        return $this->hasOne(\Modules\ColisManagment\Entities\Package::class);
    }

    public function payment()
    {
        return $this->hasOne(\Modules\Payment\Entities\Payment::class);
    }
    public function senderCity()
{
    return $this->belongsTo(\Modules\Core\Entities\City::class, 'sender_city_id');
}

public function recipientCity()
{
    return $this->belongsTo(\Modules\Core\Entities\City::class, 'recipient_city_id');
}

public function validator()
{
    return $this->belongsTo(\Modules\UserManagment\Entities\User::class, 'validated_by');
}
public function payments()
{
    return $this->hasMany(\Modules\Payment\Entities\Payment::class, 'request_id');
}

}