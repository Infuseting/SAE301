<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Support\Facades\Storage;

/**
 * QrCodeService
 * 
 * Service to generate QR codes for team registrations.
 * Each QR code contains the team ID (equ_id) and is stored in storage/app/public/qrcodes/
 * Uses SVG format (no GD extension required, works everywhere)
 */
class QrCodeService
{
    /**
     * Generate a QR code for a team registration
     * 
     * @param int $equId Team ID
     * @param int $regId Registration ID
     * @return string Path to the QR code image (relative to storage)
     */
    public function generateQrCodeForTeam(int $equId, int $regId): string
    {
        // Create QR code data (we store the team ID)
        $qrCodeData = json_encode([
            'equ_id' => $equId,
            'reg_id' => $regId,
            'type' => 'team_registration'
        ]);

        // Create QR code instance
        $qrCode = new QrCode(
            data: $qrCodeData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        // Generate SVG (no GD extension required!)
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // Define path
        $filename = "team_{$equId}_reg_{$regId}.svg";
        $path = "qrcodes/{$filename}";

        // Save to storage/app/public/qrcodes/
        Storage::disk('public')->put($path, $result->getString());

        return $path;
    }

    /**
     * Delete a QR code file
     * 
     * @param string $path Path to the QR code file
     * @return bool
     */
    public function deleteQrCode(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Check if a QR code exists
     * 
     * @param string $path Path to the QR code file
     * @return bool
     */
    public function qrCodeExists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }
}
