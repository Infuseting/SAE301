<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Services\LicenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Annotations as OA;

/**
 * Controller for managing race registrations
 * 
 * @OA\Tag(
 *     name="Race Registration",
 *     description="Endpoints for race registration and eligibility checks"
 * )
 */
class RaceRegistrationController extends Controller
{
    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        $this->licenceService = $licenceService;
    }

    /**
     * Check if user can register for a race
     *
     * @OA\Get(
     *     path="/races/{race}/registration/check",
     *     tags={"Race Registration"},
     *     summary="Check registration eligibility",
     *     description="Checks if the authenticated user is eligible to register for a specific race",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Eligibility check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="can_register", type="boolean", example=true),
     *             @OA\Property(property="has_valid_licence", type="boolean", example=true),
     *             @OA\Property(property="has_valid_pps", type="boolean", example=false),
     *             @OA\Property(property="needs_credentials", type="boolean", example=false),
     *             @OA\Property(property="licence_expiry_date", type="string", format="date", example="2026-12-31"),
     *             @OA\Property(property="pps_expiry_date", type="string", format="date", example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Race not found")
     * )
     *
     * @param Race $race
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEligibility(Race $race)
    {
        $user = auth()->user();

        // Check if user can register
        if (!Gate::allows('register', $race)) {
            return response()->json([
                'can_register' => false,
                'reason' => 'permission_denied',
                'message' => __('messages.no_permission_to_register'),
            ]);
        }

        // Check if user has valid licence or PPS
        $hasValidCredentials = $this->licenceService->hasValidCredentials($user);

        return response()->json([
            'can_register' => $hasValidCredentials,
            'has_valid_licence' => $this->licenceService->hasValidLicence($user),
            'has_valid_pps' => $this->licenceService->hasValidPps($user),
            'needs_credentials' => !$hasValidCredentials,
            'licence_expiry_date' => $user->licence_expiry_date,
            'pps_expiry_date' => $user->pps_expiry_date,
        ]);
    }

    /**
     * Register a user for a race
     *
     * @OA\Post(
     *     path="/races/{race}/register",
     *     tags={"Race Registration"},
     *     summary="Register for a race",
     *     description="Registers the authenticated user for a specific race",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Validation failed or credentials missing",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Valid licence or PPS code required"),
     *             @OA\Property(property="needs_credentials", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Permission denied"),
     *     @OA\Response(response=404, description="Race not found")
     * )
     *
     * @param Request $request
     * @param Race $race
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request, Race $race)
    {
        $user = auth()->user();

        // Check permission
        if (!Gate::allows('register', $race)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.no_permission_to_register'),
            ], 403);
        }

        // Check if user has valid credentials
        if (!$this->licenceService->hasValidCredentials($user)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.need_valid_credentials'),
                'needs_credentials' => true,
            ], 400);
        }

        try {
            // TODO: Implement actual race registration logic
            // This would involve creating a participant record, etc.

            return response()->json([
                'success' => true,
                'message' => __('messages.registration_successful'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Register a team for a race
     *
     * @param Request $request
     * @param Race $race
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerTeam(Request $request, Race $race)
    {
        $user = auth()->user();

        // Check permission (assuming 'register' policy covers this or add new one)
        if (!Gate::allows('register', $race)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.no_permission_to_register'),
            ], 403);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,equ_id',
        ]);

        $team = \App\Models\Team::where('equ_id', $validated['team_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Check limits
        $minRunners = $race->teamParams?->pae_nb_min ?? 1;
        $maxRunners = $race->teamParams?->pae_nb_max ?? 100;
        
        $currentRunners = $team->users()->count();
        $totalRunners = $currentRunners;

        if ($totalRunners < $minRunners || $totalRunners > $maxRunners) {
             return response()->json([
                'success' => false,
                'message' => "Le nombre de coureurs ($totalRunners) ne respecte pas les limites ($minRunners - $maxRunners).",
            ], 400);
        }

        try {
            // Check if team is already registered
            $existing = $race->teams()->where('teams.equ_id', $team->equ_id)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette équipe est déjà inscrite.",
                ], 400);
            }

            // Register team
            // Create payment record
            $paiId = \DB::table('inscriptions_payment')->insertGetId([
                'pai_date' => now(),
                'pai_is_paid' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Medical certificate is handled on-site, create a placeholder if needed
            $docId = $user->doc_id;
            if (!$docId) {
                // Create a placeholder medical document (to be verified on-site)
                $docId = \DB::table('medical_docs')->insertGetId([
                    'doc_num_pps' => 'PENDING-' . time(),
                    'doc_end_validity' => now()->addYear(),
                    'doc_date_added' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $race->teams()->attach($team->equ_id, [
                'pay_id' => $paiId,
                'doc_id' => $docId,
                'reg_validated' => false,
                'reg_points' => 0
            ]);

            return redirect()->back()->with('success', 'Équipe inscrite avec succès!');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a team registration from a race
     */
    public function cancelRegistration(Request $request, int $raceId, int $teamId)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $race = Race::findOrFail($raceId);
        $team = \App\Models\Team::findOrFail($teamId);

        // Check if user is part of the team
        $isTeamMember = \DB::table('has_participate')
            ->where('equ_id', $team->equ_id)
            ->where('id_users', $user->id)
            ->exists();

        if (!$isTeamMember) {
            return back()->withErrors(['error' => 'Vous ne faites pas partie de cette équipe.']);
        }

        try {
            // Remove team registration
            $race->teams()->detach($team->equ_id);

            return redirect()->back()->with('success', 'Inscription annulée avec succès.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'annulation: ' . $e->getMessage()]);
        }
    }

    /**
     * Update PPS information for a participant
     * 
     * @param Request $request
     * @param int $raceId
     * @param int $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePPS(Request $request, int $raceId, int $userId)
    {
        $authUser = auth()->user();
        if (!$authUser) {
            abort(401);
        }

        $race = Race::findOrFail($raceId);
        
        // Check if user is race manager
        $isRaceManager = $authUser->hasRole('admin') || 
            ($race->organizer && $authUser->adh_id === $race->organizer->adh_id) || 
            ($race->raid && $race->raid->club && $race->raid->club->hasManager($authUser));

        if (!$isRaceManager) {
            abort(403, 'Non autorisé à modifier les informations PPS.');
        }

        $validated = $request->validate([
            'pps_number' => 'required|string|max:255',
            'pps_expiry' => 'required|date|after:today',
        ]);

        try {
            $user = \App\Models\User::findOrFail($userId);
            
            // Update or create medical document
            if ($user->doc_id) {
                $medicalDoc = \App\Models\MedicalDoc::find($user->doc_id);
                if ($medicalDoc) {
                    $medicalDoc->update([
                        'doc_num_pps' => $validated['pps_number'],
                        'doc_end_validity' => $validated['pps_expiry'],
                    ]);
                }
            } else {
                $medicalDoc = \App\Models\MedicalDoc::create([
                    'doc_num_pps' => $validated['pps_number'],
                    'doc_end_validity' => $validated['pps_expiry'],
                    'doc_date_added' => now(),
                ]);
                $user->update(['doc_id' => $medicalDoc->doc_id]);
            }

            return redirect()->back()->with('success', 'PPS mis à jour avec succès.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du PPS: ' . $e->getMessage()]);
        }
    }

    /**
     * Confirm team payment and validate all team members
     * 
     * @param Request $request
     * @param int $raceId
     * @param int $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmTeamPayment(Request $request, int $raceId, int $teamId)
    {
        $authUser = auth()->user();
        if (!$authUser) {
            abort(401);
        }

        $race = Race::findOrFail($raceId);
        
        // Check if user is race manager
        $isRaceManager = $authUser->hasRole('admin') || 
            ($race->organizer && $authUser->adh_id === $race->organizer->adh_id) || 
            ($race->raid && $race->raid->club && $race->raid->club->hasManager($authUser));

        if (!$isRaceManager) {
            abort(403, 'Non autorisé à valider les paiements.');
        }

        try {
            $team = \App\Models\Team::findOrFail($teamId);
            
            // Get payment IDs for this team and race
            $paymentIds = \DB::table('registration')
                ->where('equ_id', $team->equ_id)
                ->where('race_id', $race->race_id)
                ->pluck('pay_id');

            if ($paymentIds->isEmpty()) {
                return back()->withErrors(['error' => 'Aucune inscription trouvée pour cette équipe.']);
            }

            // Update payment status in inscriptions_payment table
            \DB::table('inscriptions_payment')
                ->whereIn('pai_id', $paymentIds)
                ->update([
                    'pai_is_paid' => true,
                    'pai_date' => now(),
                ]);

            // Update registration validation for all team members
            \DB::table('registration')
                ->where('equ_id', $team->equ_id)
                ->where('race_id', $race->race_id)
                ->update(['reg_validated' => true]);

            return redirect()->back()->with('success', 'Paiement confirmé. Tous les membres de l\'équipe sont maintenant validés.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la confirmation du paiement: ' . $e->getMessage()]);
        }
    }
}

