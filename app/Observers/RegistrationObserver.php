<?php

namespace App\Observers;

use App\Models\Registration;
use App\Services\QrCodeService;

class RegistrationObserver
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Handle the Registration "created" event.
     * Generate QR code if registration is validated at creation
     */
    public function created(Registration $registration): void
    {
        if ($registration->reg_validated && empty($registration->qr_code_path)) {
            $this->generateQrCode($registration);
        }
    }

    /**
     * Handle the Registration "updated" event.
     * Generate QR code when registration becomes validated
     */
    public function updated(Registration $registration): void
    {
        // Check if reg_validated changed from false to true
        if ($registration->reg_validated && $registration->isDirty('reg_validated') && empty($registration->qr_code_path)) {
            $this->generateQrCode($registration);
        }
    }

    /**
     * Generate QR code for a registration
     */
    protected function generateQrCode(Registration $registration): void
    {
        try {
            $qrPath = $this->qrCodeService->generateQrCodeForTeam(
                $registration->equ_id,
                $registration->reg_id
            );
            
            // Update the registration with QR code path (without triggering the observer again)
            $registration->updateQuietly(['qr_code_path' => $qrPath]);
        } catch (\Exception $e) {
            \Log::error('Failed to generate QR code for registration', [
                'reg_id' => $registration->reg_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Registration "deleted" event.
     * Delete QR code file when registration is deleted
     */
    public function deleted(Registration $registration): void
    {
        if (!empty($registration->qr_code_path)) {
            $this->qrCodeService->deleteQrCode($registration->qr_code_path);
        }
    }

    /**
     * Handle the Registration "restored" event.
     */
    public function restored(Registration $registration): void
    {
        //
    }

    /**
     * Handle the Registration "force deleted" event.
     */
    public function forceDeleted(Registration $registration): void
    {
        if (!empty($registration->qr_code_path)) {
            $this->qrCodeService->deleteQrCode($registration->qr_code_path);
        }
    }
}
