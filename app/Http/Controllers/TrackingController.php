<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Livreur;
use App\Models\TrackingAddress;

class TrackingController extends Controller
{
   
    public function index()
    {
        return view('tracking.map');
    }
    
    public function saveAddress(Request $request)
    {
        $validated = $request->validate([
            'livreur_name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|array'
        ]);

        $trackingAddress = TrackingAddress::create([
            'livreur_name' => $validated['livreur_name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'road' => $validated['address']['road'] ?? null,
            'city' => $validated['address']['city'] ?? null,
            'postcode' => $validated['address']['postcode'] ?? null,
            'country' => $validated['address']['country'] ?? null,
            'full_address' => $validated['address']['full'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $trackingAddress
        ]);
    }

    public function getAddresses($livreurName = null)
    {
        $query = TrackingAddress::orderBy('created_at', 'desc');
        
        if ($livreurName) {
            $query->where('livreur_name', $livreurName);
        }
        
        $addresses = $query->get();
        
        return response()->json($addresses);
    }
}
