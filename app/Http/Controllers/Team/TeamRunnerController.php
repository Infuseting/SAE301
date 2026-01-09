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
     * Get all runners for a registration with their PPS status.
     *
     * @OA\Get(
     *     path="/registrations/{registration}/runners",
     *     tags={"Race Participants"},
     *     summary="Get registration runners",
     *     description="Returns list of all runners for a specific race registration with PPS information",
     *     @OA\Parameter(
     *         name="registration",
     *         in="path",
     *         required=true,
     *         description="Registration ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of runners with PPS status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="runners",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="participant_id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="has_licence", type="boolean"),
     *                     @OA\Property(property="licence_number", type="string", nullable=true),
     *                     @OA\Property(property="pps_number", type="string", nullable=true),
     *                     @OA\Property(property="pps_expiry", type="string", format="date", nullable=true),
     *                     @OA\Property(property="pps_status", type="string", enum={"pending", "verified", "rejected"}),
     *                     @OA\Property(property="has_valid_credentials", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
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
     * Add a runner to a race registration.
     *
     * @OA\Post(
     *     path="/registrations/{registration}/runners",
     *     tags={"Race Participants"},
     *     summary="Add runner to registration",
     *     description="Add a new runner to a race registration with optional PPS information",
     *     @OA\Parameter(
     *         name="registration",
     *         in="path",
     *         required=true,
     *         description="Registration ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer", example=5),
     *             @OA\Property(property="pps_number", type="string", maxLength=32, nullable=true),
     *             @OA\Property(property="pps_expiry", type="string", format="date", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Runner added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="participant",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User already participating"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
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
     * Update a runner's PPS information.
     *
     * @OA\Put(
     *     path="/participants/{participant}",
     *     tags={"Race Participants"},
     *     summary="Update runner PPS information",
     *     description="Update PPS number, expiry date, or status for a race participant",
     *     @OA\Parameter(
     *         name="participant",
     *         in="path",
     *         required=true,
     *         description="Race Participant ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="pps_number", type="string", maxLength=32, nullable=true),
     *             @OA\Property(property="pps_expiry", type="string", format="date", nullable=true),
     *             @OA\Property(property="pps_status", type="string", enum={"pending", "verified", "rejected"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="PPS updated successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     *
     * @param Request $request
     * @param RaceParticipant $participant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, RaceParticipant $participant)
    {
        $authUser = auth()->user();
        
        // Check if user is team leader, the user themselves, or admin
        $isAuthorized = $participant->registration->team->user_id === $authUser->id || 
                        $participant->user_id === $authUser->id || 
                        $authUser->hasRole('admin');
        
        if (!$isAuthorized) {
            return back()->with('error', 'Non autorisé à modifier les informations PPS.');
        }

        $validated = $request->validate([
            'pps_number' => 'nullable|string|max:32',
            'pps_expiry' => 'nullable|date|after:today',
            'pps_status' => 'nullable|in:pending,verified,rejected',
        ]);

        // Update PPS information
        $updateData = [];
        
        if (isset($validated['pps_number'])) {
            $updateData['pps_number'] = $validated['pps_number'];
        }
        
        if (isset($validated['pps_expiry'])) {
            $updateData['pps_expiry'] = $validated['pps_expiry'];
        }
        
        // If updating PPS number or expiry, reset to pending (unless admin is setting status)
        if ((isset($validated['pps_number']) || isset($validated['pps_expiry'])) && !isset($validated['pps_status'])) {
            $updateData['pps_status'] = 'pending';
        }
        
        // If admin is setting status directly
        if (isset($validated['pps_status'])) {
            $updateData['pps_status'] = $validated['pps_status'];
            if ($validated['pps_status'] !== 'pending') {
                $updateData['pps_verified_at'] = now();
            }
        }
        
        $participant->update($updateData);

        return back()->with('success', 'PPS mis à jour avec succès.');
    }

    /**
     * Remove a runner from a race registration.
     *
     * @OA\Delete(
     *     path="/participants/{participant}",
     *     tags={"Race Participants"},
     *     summary="Remove runner from registration",
     *     description="Remove a participant from a race registration",
     *     @OA\Parameter(
     *         name="participant",
     *         in="path",
     *         required=true,
     *         description="Race Participant ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Runner removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
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
     * Verify a runner's PPS (for race managers).
     *
     * @OA\Post(
     *     path="/participants/{participant}/verify-pps",
     *     tags={"Race Participants"},
     *     summary="Verify runner PPS",
     *     description="Verify or reject a runner's PPS documentation (admin/race manager only)",
     *     @OA\Parameter(
     *         name="participant",
     *         in="path",
     *         required=true,
     *         description="Race Participant ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"verified", "rejected"},
     *                 description="Defaults to 'verified' if not provided"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="PPS verification status updated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin or race manager only"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     *
     * @param Request $request
     * @param RaceParticipant $participant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyPps(Request $request, RaceParticipant $participant)
    {
        $authUser = auth()->user();
        
        // Only admins or race managers can verify PPS
        if (!$authUser->hasRole('admin') && !$authUser->hasRole('gestionnaire-raid') && !$authUser->hasRole('responsable-course')) {
            return back()->with('error', 'Non autorisé à vérifier les PPS.');
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:verified,rejected',
        ]);

        // Update PPS status - default to verified if no status provided
        $participant->update([
            'pps_status' => $validated['status'] ?? 'verified',
            'pps_verified_at' => now(),
        ]);

        $statusText = ($validated['status'] ?? 'verified') === 'verified' ? 'vérifié' : 'rejeté';

        return back()->with('success', "PPS $statusText avec succès.");
    }
}
