<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Auth;

class RaceManagementController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/me/managed-races",
     *      operationId="getManagedRaces",
     *      tags={"Race Management"},
     *      summary="Get races managed by the authenticated user",
     *      description="Returns a list of races where the user is an organizer, raid manager, or admin",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Race")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $races = Race::with(['raid', 'type'])->get();
        } else {
            // Get races where user is organizer
            $races = Race::where('adh_id', $user->adh_id)
                ->orWhereHas('raid', function ($query) use ($user) {
                    $query->where('adh_id', $user->adh_id);
                })
                ->orWhereHas('raid.club', function ($query) use ($user) {
                    $query->whereHas('users', function ($q) use ($user) {
                        $q->where('users.id', $user->id)
                            ->where('club_user.role', 'manager')
                            ->where('club_user.status', 'approved');
                    });
                })
                ->with(['raid', 'type'])
                ->get();
        }

        return response()->json($races);
    }

    /**
     * @OA\Get(
     *      path="/api/races/{race}/participants",
     *      operationId="getRaceParticipants",
     *      tags={"Race Management"},
     *      summary="Get participants for a specific race",
     *      description="Returns a detailed list of participants and their registration status for a race managed by the user",
     *      @OA\Parameter(
     *          name="race",
     *          in="path",
     *          description="ID of the race",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="registration_id", type="integer"),
     *                  @OA\Property(property="user_id", type="integer", nullable=true),
     *                  @OA\Property(property="first_name", type="string"),
     *                  @OA\Property(property="last_name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="team_name", type="string"),
     *                  @OA\Property(property="status", type="string"),
     *                  @OA\Property(property="is_leader", type="boolean"),
     *                  @OA\Property(property="has_license", type="boolean"),
     *                  @OA\Property(property="has_pps", type="boolean"),
     *                  @OA\Property(property="docs_validated", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden - User does not manage this race"
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function participants(Race $race)
    {
        $this->authorize('update', $race);

        $registrations = RaceRegistration::where('race_id', $race->race_id)
            ->where('status', '!=', 'cancelled')
            ->with(['user.member', 'user.medicalDoc', 'team.users'])
            ->get();

        $participants = [];
        $tempTeamCounter = 1;

        foreach ($registrations as $registration) {
            $teamName = $registration->is_temporary_team
                ? "ÉQUIPE TEMPORAIRE " . $tempTeamCounter++
                : ($registration->team->equ_name ?? 'Équipe permanente');

            if ($registration->is_temporary_team) {
                // Add creator
                if ($registration->is_creator_participating && $registration->user) {
                    $participants[] = $this->formatParticipantForApi($registration->user, $registration, true, $teamName);
                }

                // Add members from temp data
                $teamData = $registration->temporary_team_data ?? [];
                foreach ($teamData as $member) {
                    if (isset($member['user_id'])) {
                        $memberUser = User::find($member['user_id']);
                        if ($memberUser) {
                            $participants[] = $this->formatParticipantForApi($memberUser, $registration, false, $teamName);
                        }
                    } else {
                        $participants[] = [
                            'registration_id' => $registration->reg_id,
                            'user_id' => null,
                            'first_name' => $member['name'] ?? explode('@', $member['email'])[0],
                            'last_name' => '',
                            'email' => $member['email'],
                            'team_name' => $teamName,
                            'status' => 'invitation_pending',
                            'is_leader' => false,
                            'has_license' => false,
                            'has_pps' => false,
                            'docs_validated' => false,
                        ];
                    }
                }
            } else {
                // Permanent team
                if ($registration->team) {
                    foreach ($registration->team->users as $teamUser) {
                        $isLeader = (int) $teamUser->id === (int) $registration->user_id;
                        $participants[] = $this->formatParticipantForApi($teamUser, $registration, $isLeader, $teamName);
                    }
                }
            }
        }

        return response()->json($participants);
    }

    /**
     * @OA\Patch(
     *      path="/api/registrations/{registration}/validate-docs",
     *      operationId="validateRegistrationDocs",
     *      tags={"Race Management"},
     *      summary="Validate or flag documents for a registration",
     *      description="Allows a manager to manually validate registration documents or flag them as invalid",
     *      @OA\Parameter(
     *          name="registration",
     *          in="path",
     *          description="ID of the registration",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", enum={"confirmed", "missing_credentials"}, example="confirmed"),
     *              @OA\Property(property="admin_notes", type="string", example="Document illisible, merci de renvoyer.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function validateDocuments(Request $request, RaceRegistration $registration)
    {
        $this->authorize('update', $registration->race);

        $validated = $request->validate([
            'status' => 'required|in:confirmed,missing_credentials',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $registration->update([
            'status' => $validated['status'],
            // Assuming there's an admin_notes or similar field, or we use activity log
            // For now, let's assume status is enough or we use metadata
            'metadata' => array_merge($registration->metadata ?? [], [
                'validation_notes' => $validated['admin_notes'] ?? '',
                'validated_at' => now()->toDateTimeString(),
                'validated_by' => Auth::id(),
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Le statut de l\'inscription a été mis à jour.',
            'registration' => $registration
        ]);
    }

    private function formatParticipantForApi(User $user, RaceRegistration $registration, bool $isLeader, string $teamName): array
    {
        return [
            'registration_id' => $registration->reg_id,
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'team_name' => $teamName,
            'status' => $registration->status,
            'is_leader' => $isLeader,
            'has_license' => $user->member && $user->member->adh_license ? true : false,
            'has_pps' => $user->medicalDoc && $user->medicalDoc->doc_num_pps ? true : false,
            'docs_validated' => $registration->status === 'confirmed',
        ];
    }
}
