<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RaceParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * Controller for managing race participants (runners) for specific race registrations
 * 
 * @OA\Tag(
 *     name="Race Participants",
 *     description="Endpoints for managing runners in race registrations and their PPS information"
 * )
 */
class TeamRunnerController extends Controller
{
    /**
     * Get all runners for a registration with their PPS status
     *
     * @param Registration $registration
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Registration $registration): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        
        // Check if user is team leader, race manager, or admin
        $isAuthorized = $registration->team->user_id === $user->id || 
                        $user->hasRole('admin') || 
                        $user->hasRole('gestionnaire-raid') || 
                        $user->hasRole('responsable-course');
        
        if (!$isAuthorized) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à voir les coureurs de cette inscription.',
            ], 403);
        }

        $runners = $registration->participants()
            ->with(['user.member'])
            ->get()
            ->map(function ($participant) {
                $user = $participant->user;
                return [
                    'id' => $user->id,
                    'participant_id' => $participant->rpa_id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'has_licence' => $participant->hasValidLicence(),
                    'licence_number' => $user->member?->adh_license,
                    'pps_number' => $participant->pps_number,
                    'pps_expiry' => $participant->pps_expiry,
                    'pps_status' => $participant->pps_status,
                    'pps_verified_at' => $participant->pps_verified_at,
                    'bib_number' => $participant->bib_number,
                    'has_valid_credentials' => $participant->hasValidCredentials(),
                ];
            });

        return response()->json([
            'success' => true,
            'runners' => $runners,
            'registration' => [
                'id' => $registration->reg_id,
                'team_name' => $registration->team->equ_name,
                'race_name' => $registration->race->race_name,
            ],
        ]);
    }

    /**
     * Add a runner to a race registration
     *
     * @param Request $request
     * @param Registration $registration
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Registration $registration): \Illuminate\Http\JsonResponse
    {
        $authUser = auth()->user();
        
        // Check if user is team leader or admin
        if ($registration->team->user_id !== $authUser->id && !$authUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à modifier cette inscription.',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'pps_number' => 'nullable|string|max:32',
            'pps_expiry' => 'nullable|date|after:today',
        ]);

        $userToAdd = User::findOrFail($validated['user_id']);

        // Check if user is already a participant in this registration
        $exists = $registration->participants()->where('user_id', $userToAdd->id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur participe déjà à cette course.',
            ], 400);
        }

        // Create participant record
        $participant = RaceParticipant::create([
            'reg_id' => $registration->reg_id,
            'user_id' => $userToAdd->id,
            'pps_number' => $validated['pps_number'] ?? null,
            'pps_expiry' => $validated['pps_expiry'] ?? null,
            'pps_status' => !empty($validated['pps_number']) ? 'pending' : 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Coureur ajouté avec succès.',
            'participant' => [
                'id' => $participant->rpa_id,
                'user_id' => $participant->user_id,
            ],
        ]);
    }

    /**
     * Update a runner's PPS information
     *
     * @param Request $request
     * @param RaceParticipant $participant
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, RaceParticipant $participant): \Illuminate\Http\JsonResponse
    {
        $authUser = auth()->user();
        
        // Check if user is team leader, the user themselves, or admin
        $isAuthorized = $participant->registration->team->user_id === $authUser->id || 
                        $participant->user_id === $authUser->id || 
                        $authUser->hasRole('admin');
        
        if (!$isAuthorized) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à modifier les informations PPS.',
            ], 403);
        }

        $validated = $request->validate([
            'pps_number' => 'required|string|max:32',
            'pps_expiry' => 'required|date|after:today',
        ]);

        // Update PPS information
        $participant->update([
            'pps_number' => $validated['pps_number'],
            'pps_expiry' => $validated['pps_expiry'],
            'pps_status' => 'pending', // Reset to pending when updated
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PPS mis à jour avec succès.',
        ]);
    }

    /**
     * Remove a runner from a race registration
     *
     * @param RaceParticipant $participant
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(RaceParticipant $participant): \Illuminate\Http\JsonResponse
    {
        $authUser = auth()->user();
        
        // Check if user is team leader or admin
        if ($participant->registration->team->user_id !== $authUser->id && !$authUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à modifier cette inscription.',
            ], 403);
        }

        $participant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coureur retiré de la course.',
        ]);
    }

    /**
     * Verify a runner's PPS (for race managers)
     *
     * @param Request $request
     * @param RaceParticipant $participant
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPps(Request $request, RaceParticipant $participant): \Illuminate\Http\JsonResponse
    {
        $authUser = auth()->user();
        
        // Only admins or race managers can verify PPS
        if (!$authUser->hasRole('admin') && !$authUser->hasRole('gestionnaire-raid') && !$authUser->hasRole('responsable-course')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à vérifier les PPS.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
        ]);

        // Update PPS status
        $participant->update([
            'pps_status' => $validated['status'],
            'pps_verified_at' => now(),
        ]);

        $statusText = $validated['status'] === 'verified' ? 'vérifié' : 'rejeté';

        return response()->json([
            'success' => true,
            'message' => "PPS $statusText avec succès.",
        ]);
    }
}
