<?php

namespace Modules\ColisManagment\Repositories;

use Modules\Core\Entities\City;
use Modules\ColisManagment\Models\Center;
use Modules\Payment\Entities\Payment;
use Modules\ColisManagment\Entities\Package;
use Modules\ColisManagment\Entities\Request;
use Modules\ColisManagment\Entities\DeliveryType;
use Modules\ColisManagment\Entities\RegionTypePricing;


class RequestRepository
{
    /**
     * Get city with their centers
     */
    public function cityWithCenters()
    {
        return City::where('is_active', true)
            ->with(['centers' => function($query) {
                $query->where('is_active', true)
                      ->select('id', 'city_id', 'name', 'address', 'phone');
            }])
            ->select('id', 'name')
            ->get();
    }

    /**
     * Get delivery types with pricing
     */
    public function deliveryTypesWithPricing()
    {
        return DeliveryType::where('is_active', true)
            ->with(['pricings' => function($query) {
                $query->where('is_active', true)
                      ->with('city:id,name');
            }])
            ->get();
    }

    /**
     * Get pricing for city and delivery type
     */
    public function getPricing(int $cityId, int $deliveryTypeId)
    {
        return RegionTypePricing::where('city_id', $cityId)
            ->where('delivery_type_id', $deliveryTypeId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create new request
     */
    public function create(array $data)
    {
        return Request::create($data);
    }

    /**
     * Create new package
     */
    public function createPackage(array $data)
    {
        return Package::create($data);
    }

    /**
     * Create payment
     */
    public function createPayment(array $data)
    {
        return Payment::create($data);
    }

    /**
     * Find request by ID or fail
     */
    public function findOrFail(int $id)
    {
        return Request::findOrFail($id);
    }

    /**
     * Find request with all relations
     */
    public function findWithRelations(int $id)
    {
        return Request::with([
            'user:id,name,email,phone',
            'center.city',
            'deliveryType',
            'senderCity',
            'recipientCity',
            'package',
            'payment',
            'validator:id,name'
        ])->findOrFail($id);
    }

    /**
     * Check if request code exists
     */
    public function codeExists(string $code): bool
    {
        return Request::where('code', $code)->exists();
    }

    /**
     * Check if package code exists
     */
    public function packageCodeExists(string $code): bool
    {
        return Package::where('code', $code)->exists();
    }

    /**
     * Get last invoice number
     */
    public function getLastInvoiceNumber()
    {
        return Payment::orderBy('id', 'desc')
            ->value('invoice_number');
    }

    /**
     * Build list query with filters
     */
    public function buildListQuery(array $filters)
    {
        $query = Request::with([
            'user:id,name,email',
            'center:id,name',
            'deliveryType:id,name',
            'senderCity:id,name',
            'recipientCity:id,name',
            'package:id,request_id,code,weight,status',
            'payment:id,request_id,invoice_number,status'
        ]);

        // Filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['center_id'])) {
            $query->where('center_id', $filters['center_id']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query;
    }

    /**
     * Build search query
     */
    public function buildSearchQuery(array $keywords, array $filters)
    {
        $query = Request::with([
            'user:id,name,email',
            'center:id,name',
            'deliveryType:id,name',
            'package:id,request_id,code'
        ]);

        // Appliquer les filtres actifs
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Recherche par keywords
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('code', 'LIKE', "%{$keyword}%")
                  ->orWhere('sender_full_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('recipient_full_name', 'LIKE', "%{$keyword}%")
                  ->orWhereHas('user', function ($userQuery) use ($keyword) {
                      $userQuery->where('name', 'LIKE', "%{$keyword}%")
                               ->orWhere('email', 'LIKE', "%{$keyword}%");
                  });
            }
        });

        return $query;
    }
}