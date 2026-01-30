<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\Registration as RaceRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RaceManagementController extends Controller
{
    use AuthorizesRequests;
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
                    $query->whereHas('allMembers', function ($q) use ($user) {
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
            ->with(['team', 'participants.user.member'])
            ->get();

        $participants = [];

        foreach ($registrations as $registration) {
            $teamName = $registration->team->equ_name ?? 'Sans équipe';

            foreach ($registration->participants as $participant) {
                $user = $participant->user;
                if (!$user)
                    continue;

                $participants[] = [
                    'registration_id' => $registration->reg_id,
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'team_name' => $teamName,
                    'status' => $registration->reg_validated ? 'confirmed' : 'pending',
                    'is_leader' => false, // Could be determined by team owner or first participant
                    'has_license' => $user->member && $user->member->adh_license ? true : false,
                    'has_pps' => $participant->pps_number ? true : false,
                    'docs_validated' => (bool) $registration->reg_validated,
                ];
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
            'reg_validated' => $validated['status'] === 'confirmed',
            // We can store notes if there's a field, or just skip if not available
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
