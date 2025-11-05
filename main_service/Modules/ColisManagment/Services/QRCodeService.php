<?php

namespace Modules\ColisManagment\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Generate QR code for request
     * 
     * @param object $request
     * @return string Base64 encoded image
     */
    // public function generateForRequest($request): string
    // {
    //     $data = [
    //         'code' => $request->code,
    //         'date' => $request->created_at->format('Y-m-d'),
    //         'amount' => (float) $request->amount,
    //         'delivery_type' => $request->deliveryType->name ?? 'N/A',
    //         'recipient' => $request->recipient_full_name,
    //         'city' => $request->recipientCity->name ?? 'N/A',
    //     ];

    //     $qrCode = QrCode::format('png')
    //         ->size(300)
    //         ->errorCorrection('H')
    //         ->margin(1)
    //         ->generate(json_encode($data));

    //     return 'data:image/png;base64,' . base64_encode($qrCode);
    // }

    /**
     * Generate QR code for package
     * 
     * @param object $package
     * @return string Base64 encoded image
     */
    // public function generateForPackage($package): string
    // {
    //     $data = [
    //         'package_code' => $package->code,
    //         'request_code' => $package->request->code,
    //         'weight' => (float) $package->weight,
    //         'content_type' => $package->content_type,
    //     ];

    //     $qrCode = QrCode::format('png')
    //         ->size(250)
    //         ->errorCorrection('H')
    //         ->margin(1)
    //         ->generate(json_encode($data));

    //     return 'data:image/png;base64,' . base64_encode($qrCode);
    // }

    /**
     * Decode QR code data
     * 
     * @param string $qrCodeData
     * @return array
     */
    public function decode(string $qrCodeData): array
    {
        try {
            return json_decode($qrCodeData, true);
        } catch (\Exception $e) {
            throw new \Exception("QR code invalide ou corrompu");
        }
    }
}