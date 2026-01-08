<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

/**
 * @OA\Tag(
 *     name="Admin - Club Approval",
 *     description="Admin endpoints for club approval management"
 * )
 */
class ClubApprovalController extends Controller
{
    /**
     * Display pending clubs awaiting approval.
     *
     * @OA\Get(
     *     path="/api/admin/clubs/pending",
     *     tags={"Admin - Club Approval"},
     *     summary="Get pending clubs",
     *     description="Returns a list of clubs awaiting approval",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Club"))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - requires accept-club permission")
     * )
     */
    public function index(): Response
    {
        $pendingClubs = Club::pending()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/ClubApproval', [
            'pendingClubs' => $pendingClubs,
        ]);
    }

    /**
     * Approve a pending club.
     *
     * @OA\Post(
     *     path="/api/admin/clubs/{id}/approve",
     *     tags={"Admin - Club Approval"},
     *     summary="Approve a club",
     *     description="Approves a pending club and assigns club-manager role to creator",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club approved successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function approve(Club $club): RedirectResponse
    {
        if ($club->is_approved) {
            return back()->with('error', __('messages.club_already_approved'));
        }

        \Illuminate\Support\Facades\Log::info('Approving club ' . $club->club_id);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($club) {
                // Approve the club
                $club->is_approved = true;
                $club->approved_by = auth()->id();
                $club->approved_at = now();
                $club->save();

                // Assign club-manager role to creator
                $creator = $club->creator;
                if (!$creator->hasRole('club-manager')) {
                    $creator->assignRole('club-manager');
                }

                // Add creator as manager in club_user pivot
                $club->allMembers()->syncWithoutDetaching([
                    $creator->id => [
                        'role' => 'manager',
                        'status' => 'approved',
                    ]
                ]);

                activity()
                    ->performedOn($club)
                    ->causedBy(auth()->user())
                    ->withProperties(['creator' => $creator->name])
                    ->log('Club approved');
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Club approval failed: ' . $e->getMessage());
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }

        return back()->with('success', __('messages.club_approved', ['name' => $club->club_name]));
    }

    /**
     * Reject a pending club.
     *
     * @OA\Post(
     *     path="/api/admin/clubs/{id}/reject",
     *     tags={"Admin - Club Approval"},
     *     summary="Reject a club",
     *     description="Rejects a pending club with optional reason",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", example="Duplicate club")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club rejected successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function reject(Request $request, Club $club): RedirectResponse
    {
        if ($club->is_approved) {
            return back()->with('error', __('messages.cannot_reject_approved_club'));
        }

        $reason = $request->input('reason', 'No reason provided');

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->withProperties([
                'reason' => $reason,
                'club_name' => $club->club_name,
            ])
            ->log('Club rejected');

        // Delete the club
        $club->delete();

        return back()->with('success', __('messages.club_rejected'));
    }
}
