<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    protected $table = 'city'; // Specify the correct table name

    protected $fillable = [
        'name',
        'code',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    /**
     * Get centers in this city
     */
    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    /**
     * Scope for active city only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}