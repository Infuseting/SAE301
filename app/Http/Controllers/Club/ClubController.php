<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Tag(
 *     name="Clubs",
 *     description="Club management endpoints"
 * )
 */
class ClubController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of approved clubs.
     *
     * @OA\Get(
     *     path="/api/clubs",
     *     tags={"Clubs"},
     *     summary="Get list of approved clubs",
     *     description="Returns a paginated list of approved clubs with optional search and filters",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by club name or city",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Filter by city",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Club"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = Club::query()
            ->with(['creator', 'members'])
            ->where('is_approved', true);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('club_name', 'like', "%{$search}%")
                    ->orWhere('club_city', 'like', "%{$search}%");
            });
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('club_city', $request->input('city'));
        }

        $clubs = $query->paginate(12);
        
        // Add user membership status to each club
        if ($user) {
            $clubs->getCollection()->transform(function ($club) use ($user) {
                $membership = \DB::table('club_user')
                    ->where('club_id', $club->club_id)
                    ->where('user_id', $user->id)
                    ->first();
                    
                $club->user_membership_status = $membership ? $membership->status : null;
                return $club;
            });
        }

        return Inertia::render('Clubs/Index', [
            'clubs' => $clubs,
            'filters' => $request->only(['search', 'city']),
        ]);
    }

    /**
     * Show the form for creating a new club.
     */
    public function create(): Response
    {
        $this->authorize('create', Club::class);

        return Inertia::render('Clubs/Create');
    }

    /**
     * Store a newly created club in storage.
     *
     * @OA\Post(
     *     path="/api/clubs",
     *     tags={"Clubs"},
     *     summary="Create a new club",
     *     description="Creates a new club (pending approval)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"club_name","club_street","club_city","club_postal_code"},
     *                 @OA\Property(property="club_name", type="string", example="Club Orienteering Paris"),
     *                 @OA\Property(property="club_street", type="string", example="123 Rue de la Paix"),
     *                 @OA\Property(property="club_city", type="string", example="Paris"),
     *                 @OA\Property(property="club_postal_code", type="string", example="75001"),
     *                 @OA\Property(property="ffso_id", type="string", example="FFSO-12345"),
     *                 @OA\Property(property="description", type="string", example="Club description"),
     *                 @OA\Property(property="club_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Club created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Club::class);

        $validated = $request->validate([
            'club_name' => 'required|string|max:100',
            'club_street' => 'required|string|max:100',
            'club_city' => 'required|string|max:100',
            'club_postal_code' => 'required|string|max:20',
            'ffso_id' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'club_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('club_image')) {
            $validated['club_image'] = $request->file('club_image')->store('clubs', 'public');
        }

        $club = Club::create([
            ...$validated,
            'created_by' => auth()->id(),
            'is_approved' => false, // Clubs require approval by default
        ]);

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->log('Club created (pending approval)');

        return redirect()->route('clubs.show', $club->club_id)
            ->with('success', __('messages.club_created_pending'));
    }

    /**
     * Display the specified club.
     *
     * @OA\Get(
     *     path="/api/clubs/{id}",
     *     tags={"Clubs"},
     *     summary="Get club details",
     *     description="Returns club details. Members list is only visible to club members.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function show(Club $club): Response
    {
        // If club is not approved, only creator can view it
        if (!$club->is_approved && auth()->id() !== $club->created_by) {
            abort(404);
        }

        $club->load(['creator', 'approver']);

        // Load raids with their registration periods and races
        $club->load(['raids' => function($query) {
            $query->with(['registrationPeriod', 'races' => function($q) {
                $q->with(['type']);
            }]);
        }]);

        $user = auth()->user();
        $isMember = $user && $club->hasMember($user);
        // Admin can manage all clubs, otherwise check if user is club manager
        $isManager = $user && ($user->hasRole('admin') || $club->hasManager($user));
        
        // Check if user has a pending request
        $membershipStatus = null;
        if ($user) {
            $membership = \DB::table('club_user')
                ->where('club_id', $club->club_id)
                ->where('user_id', $user->id)
                ->first();
            $membershipStatus = $membership ? $membership->status : null;
        }

        // Only show members if user is a member
        if ($isMember) {
            $club->load(['members', 'managers']);

            // Managers can see pending requests
            if ($isManager) {
                $club->load('pendingRequests');
            }
        }

        // Add status helpers to raids for frontend
        $club->raids->each(function($raid) {
            $raid->is_open = $raid->isOpen();
            $raid->is_upcoming = $raid->isUpcoming();
            $raid->is_finished = $raid->isFinished();
            
            $raid->races->each(function($race) {
                $race->is_open = $race->isOpen();
                $race->registration_upcoming = $race->isRegistrationUpcoming();
            });
        });

        return Inertia::render('Clubs/Show', [
            'club' => $club,
            'isMember' => $isMember,
            'isManager' => $isManager,
            'membershipStatus' => $membershipStatus,
        ]);
    }

    /**
     * Show the form for editing the specified club.
     */
    public function edit(Club $club): Response
    {
        // Use policy for authorization (handles admin and club managers)
        $this->authorize('update', $club);

        $club->load(['members', 'pendingRequests']);

        return Inertia::render('Clubs/Edit', [
            'club' => $club,
        ]);
    }

    /**
     * Update the specified club in storage.
     *
     * @OA\Put(
     *     path="/api/clubs/{id}",
     *     tags={"Clubs"},
     *     summary="Update club details",
     *     description="Updates club details (club managers only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="club_name", type="string"),
     *                 @OA\Property(property="club_street", type="string"),
     *                 @OA\Property(property="club_city", type="string"),
     *                 @OA\Property(property="club_postal_code", type="string"),
     *                 @OA\Property(property="ffso_id", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="club_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function update(Request $request, Club $club): RedirectResponse
    {
        // Use policy for authorization (handles admin and club managers)
        $this->authorize('update', $club);

        $validated = $request->validate([
            'club_name' => 'required|string|max:100',
            'club_street' => 'required|string|max:100',
            'club_city' => 'required|string|max:100',
            'club_postal_code' => 'required|string|max:20',
            'ffso_id' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'club_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('club_image')) {
            // Delete old image if exists
            if ($club->club_image) {
                \Storage::disk('public')->delete($club->club_image);
            }
            $validated['club_image'] = $request->file('club_image')->store('clubs', 'public');
        }

        $club->update($validated);

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->log('Club updated');

        return redirect()->route('clubs.show', $club->club_id)
            ->with('success', __('messages.club_updated'));
    }

    /**
     * Remove the specified club from storage.
     *
     * @OA\Delete(
     *     path="/api/clubs/{id}",
     *     tags={"Clubs"},
     *     summary="Delete a club",
     *     description="Deletes a club (admin or club manager only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Club deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function destroy(Club $club): RedirectResponse
    {
        $user = auth()->user();

        // Only admin or club manager can delete
        if (!$user->hasRole('admin') && !$club->hasManager($user)) {
            abort(403, 'Only admins or club managers can delete this club');
        }

        activity()
            ->performedOn($club)
            ->causedBy($user)
            ->log('Club deleted');

        $club->delete();

        return redirect()->route('clubs.index')
            ->with('success', __('messages.club_deleted'));
    }
}
