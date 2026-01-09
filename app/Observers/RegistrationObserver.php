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
     * Assign dossard number and generate QR code if registration is validated at creation
     */
    public function created(Registration $registration): void
    {
        // Auto-assign dossard number if not already set
        if (empty($registration->reg_dossard)) {
            $this->assignDossard($registration);
        }

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
            
        }
    }

    /**
     * Assign the next available dossard number for the race
     */
    protected function assignDossard(Registration $registration): void
    {
        try {
            // Get the highest dossard number for this race
            $maxDossard = Registration::where('race_id', $registration->race_id)
                ->where('reg_id', '!=', $registration->reg_id)
                ->whereNotNull('reg_dossard')
                ->max('reg_dossard');

            $nextDossard = ($maxDossard ?? 0) + 1;

            // Update directly in database to ensure it persists
            \DB::table('registration')
                ->where('reg_id', $registration->reg_id)
                ->update(['reg_dossard' => $nextDossard]);
            
            // Also update the model instance
            $registration->reg_dossard = $nextDossard;
       
        } catch (\Exception $e) {
           
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
