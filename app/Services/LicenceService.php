<?php

namespace App\Services;

use App\Models\User;
use App\Models\Member;
use App\Models\MedicalDoc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service to manage user licences and PPS codes
 * Licence is stored in members table (adh_license, adh_end_validity)
 * PPS is stored in medical_docs table (doc_num_pps, doc_end_validity)
 */
class LicenceService
{
    /**
     * Add or update a user's licence number via Member record
     *
     * @param User $user The user to update
     * @param string $licenceNumber The licence number to add
     * @return bool
     */
    public function addLicence(User $user, string $licenceNumber): bool
    {
        return DB::transaction(function () use ($user, $licenceNumber) {
            // Create or update member record
            $member = $user->member;
            
            if (!$member) {
                $member = Member::create([
                    'adh_license' => $licenceNumber,
                    'adh_date_added' => Carbon::now(),
                    'adh_end_validity' => Carbon::now()->addYear(),
                ]);
                $user->adh_id = $member->adh_id;
                $user->save();
            } else {
                $member->adh_license = $licenceNumber;
                $member->adh_date_added = Carbon::now();
                $member->adh_end_validity = Carbon::now()->addYear();
                $member->save();
            }

            // Automatically assign adherent role if licence is valid
            $this->checkAndAssignAdherentRole($user);

            return true;
        });
    }

    /**
     * Add or update a user's PPS code via MedicalDoc record
     *
     * @param User $user The user to update
     * @param string $ppsCode The PPS code to add
     * @return bool
     */
    public function addPpsCode(User $user, string $ppsCode): bool
    {
        return DB::transaction(function () use ($user, $ppsCode) {
            // Create or update medical doc record
            $medicalDoc = $user->medicalDoc;
            
            if (!$medicalDoc) {
                $medicalDoc = MedicalDoc::create([
                    'doc_num_pps' => $ppsCode,
                    'doc_date_added' => Carbon::now(),
                    'doc_end_validity' => Carbon::now()->addMonths(3),
                ]);
                $user->doc_id = $medicalDoc->doc_id;
                $user->save();
            } else {
                $medicalDoc->doc_num_pps = $ppsCode;
                $medicalDoc->doc_date_added = Carbon::now();
                $medicalDoc->doc_end_validity = Carbon::now()->addMonths(3);
                $medicalDoc->save();
            }

            return true;
        });
    }

    /**
     * Check if a user has a valid licence
     *
     * @param User $user
     * @return bool
     */
    public function hasValidLicence(User $user): bool
    {
        $member = $user->member;
        
        if (!$member || !$member->adh_license || !$member->adh_end_validity) {
            return false;
        }

        return Carbon::parse($member->adh_end_validity)->isFuture();
    }

    /**
     * Check if a user has a valid PPS code
     *
     * @param User $user
     * @return bool
     */
    public function hasValidPps(User $user): bool
    {
        $medicalDoc = $user->medicalDoc;
        
        if (!$medicalDoc || !$medicalDoc->doc_num_pps || !$medicalDoc->doc_end_validity) {
            return false;
        }

        return Carbon::parse($medicalDoc->doc_end_validity)->isFuture();
    }

    /**
     * Check if a user has valid licence or PPS
     *
     * @param User $user
     * @return bool
     */
    public function hasValidCredentials(User $user): bool
    {
        return $this->hasValidLicence($user) || $this->hasValidPps($user);
    }

    /**
     * Check and assign adherent role if user has valid licence
     *
     * @param User $user
     * @return void
     */
    public function checkAndAssignAdherentRole(User $user): void
    {
        // Check if the adherent role exists before trying to assign/remove it
        if (!$this->roleExists('adherent')) {
            return;
        }

        if ($this->hasValidLicence($user)) {
            if (!$user->hasRole('adherent')) {
                $user->assignRole('adherent');
            }
        } else {
            if ($user->hasRole('adherent')) {
                // Only remove adherent role if user doesn't have other dependent roles
                if (!$user->hasAnyRole(['responsable-club', 'gestionnaire-raid', 'responsable-course'])) {
                    $user->removeRole('adherent');
                }
            }
        }
    }

    /**
     * Check if a role exists in the database
     *
     * @param string $roleName
     * @return bool
     */
    protected function roleExists(string $roleName): bool
    {
        try {
            return \Spatie\Permission\Models\Role::where('name', $roleName)->where('guard_name', 'web')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove expired licences and update roles
     *
     * @return int Number of users affected
     */
    public function cleanupExpiredLicences(): int
    {
        $affectedUsers = 0;

        $users = User::whereHas('member', function ($query) {
            $query->whereNotNull('adh_end_validity')
                  ->where('adh_end_validity', '<', Carbon::now());
        })->get();

        foreach ($users as $user) {
            $this->checkAndAssignAdherentRole($user);
            $affectedUsers++;
        }

        return $affectedUsers;
    }

    /**
     * Check if user can be assigned a role that requires adherent status
     *
     * @param User $user
     * @param string $roleName
     * @return bool
     */
    public function canAssignRole(User $user, string $roleName): bool
    {
        $rolesRequiringAdherent = ['responsable-club', 'gestionnaire-raid', 'responsable-course'];

        if (in_array($roleName, $rolesRequiringAdherent)) {
            return $this->hasValidLicence($user);
        }

        return true;
    }

    /**
     * Assign a role to a user, ensuring adherent role is also assigned if needed
     *
     * @param User $user
     * @param string $roleName
     * @return bool
     * @throws \Exception
     */
    public function assignRoleWithCheck(User $user, string $roleName): bool
    {
        if (!$this->canAssignRole($user, $roleName)) {
            throw new \Exception("User must have a valid licence to be assigned the role: {$roleName}");
        }

        $user->assignRole($roleName);

        // Ensure adherent role is also assigned for specific roles
        $rolesRequiringAdherent = ['responsable-club', 'gestionnaire-raid', 'responsable-course'];
        if (in_array($roleName, $rolesRequiringAdherent) && !$user->hasRole('adherent')) {
            $user->assignRole('adherent');
        }

        return true;
    }

    /**
     * Get licence info for a user
     *
     * @param User $user
     * @return array
     */
    public function getLicenceInfo(User $user): array
    {
        $member = $user->member;
        $medicalDoc = $user->medicalDoc;

        return [
            'licence_number' => $member?->adh_license,
            'licence_date_added' => $member?->adh_date_added,
            'licence_expiry_date' => $member?->adh_end_validity,
            'has_valid_licence' => $this->hasValidLicence($user),
            'pps_code' => $medicalDoc?->doc_num_pps,
            'pps_date_added' => $medicalDoc?->doc_date_added,
            'pps_expiry_date' => $medicalDoc?->doc_end_validity,
            'has_valid_pps' => $this->hasValidPps($user),
            'has_valid_credentials' => $this->hasValidCredentials($user),
        ];
    }
}
