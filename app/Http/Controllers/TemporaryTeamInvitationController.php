<?php

namespace App\Http\Controllers;

use App\Models\TemporaryTeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

/**
 * Controller for handling temporary team invitations.
 */
class TemporaryTeamInvitationController extends Controller
{
    /**
     * Display invitation details.
     */
    public function show(string $token)
    {
        $invitation = TemporaryTeamInvitation::with(['registration.race.raid', 'inviter'])
            ->where('token', $token)
            ->firstOrFail();

        // Check if expired
        if ($invitation->isExpired()) {
            return Inertia::render('Invitation/InvitationExpired', [
                'invitation' => [
                    'email' => $invitation->email,
                    'race_name' => $invitation->registration->race->race_name ?? 'Course',
                    'expired_at' => $invitation->expires_at->format('d/m/Y à H:i'),
                ],
            ]);
        }

        // Check if already accepted/rejected
        if ($invitation->status !== 'pending') {
            return Inertia::render('Invitation/InvitationAlreadyProcessed', [
                'status' => $invitation->status,
                'race_name' => $invitation->registration->race->race_name ?? 'Course',
            ]);
        }

        return Inertia::render('Invitation/InvitationShow', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'expires_at' => $invitation->expires_at->format('d/m/Y à H:i'),
                'inviter' => [
                    'name' => $invitation->inviter->name,
                    'email' => $invitation->inviter->email,
                ],
                'race' => [
                    'id' => $invitation->registration->race->race_id,
                    'name' => $invitation->registration->race->race_name,
                    'description' => $invitation->registration->race->race_description,
                    'date_start' => $invitation->registration->race->race_date_start,
                    'location' => $invitation->registration->race->raid->raid_location ?? null,
                ],
            ],
        ]);
    }

    /**
     * Accept invitation (for existing users).
     */
    public function accept(Request $request, string $token)
    {
        $invitation = TemporaryTeamInvitation::where('token', $token)->firstOrFail();
        $user = Auth::user();

        // Verify user email matches invitation
        if ($user->email !== $invitation->email) {
            return back()->with('error', 'Cette invitation n\'est pas pour votre adresse email.');
        }

        // Check if expired
        if ($invitation->isExpired()) {
            return back()->with('error', 'Cette invitation a expiré.');
        }

        // Check if user has valid credentials (PPS or License)
        $warning = null;
        if (!$user->hasValidCredentials()) {
            $warning = 'Vous avez rejoint l\'équipe avec succès ! N\'oubliez pas de compléter votre profil (licence ou PPS) pour valider votre participation.';
        }

        // Accept invitation
        $invitation->accept();

        // Update temporary_team_data in registration
        $registration = $invitation->registration;
        $teamData = $registration->temporary_team_data ?? [];

        foreach ($teamData as &$member) {
            if ($member['email'] === $invitation->email) {
                $member['status'] = 'accepted';
                $member['user_id'] = Auth::id();
                break;
            }
        }

        $registration->update(['temporary_team_data' => $teamData]);

        if (request()->wantsJson() && !request()->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => $warning ?? 'Vous avez rejoint l\'équipe avec succès !',
                'race_id' => $registration->race_id,
            ]);
        }

        return redirect()->route('races.show', $registration->race_id)
            ->with('success', $warning ?? 'Vous avez rejoint l\'équipe avec succès !');
    }

    /**
     * Reject invitation.
     */
    public function reject(Request $request, string $token)
    {
        $invitation = TemporaryTeamInvitation::where('token', $token)->firstOrFail();
        $user = Auth::user();

        // Verify user email matches invitation
        if ($user->email !== $invitation->email) {
            return back()->with('error', 'Cette invitation n\'est pas pour votre adresse email.');
        }

        // Reject invitation
        $invitation->reject();

        // Update temporary_team_data in registration
        $registration = $invitation->registration;
        $teamData = $registration->temporary_team_data ?? [];

        foreach ($teamData as &$member) {
            if ($member['email'] === $invitation->email) {
                $member['status'] = 'rejected';
                break;
            }
        }

        $registration->update(['temporary_team_data' => $teamData]);

        if (request()->wantsJson() && !request()->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => 'Invitation refusée.',
            ]);
        }

        return redirect()->route('profile.invitations')->with('success', 'Invitation refusée.');
    }

    /**
     * Show registration form for new users.
     */
    public function register(string $token)
    {
        $invitation = TemporaryTeamInvitation::with(['registration.race', 'inviter'])
            ->where('token', $token)
            ->firstOrFail();

        // Check if expired
        if ($invitation->isExpired()) {
            return Inertia::render('Invitation/InvitationExpired', [
                'invitation' => [
                    'email' => $invitation->email,
                    'race_name' => $invitation->registration->race->race_name ?? 'Course',
                    'expired_at' => $invitation->expires_at->format('d/m/Y à H:i'),
                ],
            ]);
        }

        return Inertia::render('Invitation/InvitationRegister', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'race_name' => $invitation->registration->race->race_name,
                'inviter_name' => $invitation->inviter->name,
            ],
        ]);
    }

    /**
     * Create account and accept invitation.
     */
    public function storeRegistration(Request $request, string $token)
    {
        $invitation = TemporaryTeamInvitation::where('token', $token)->firstOrFail();

        // Check if expired
        if ($invitation->isExpired()) {
            return back()->with('error', 'Cette invitation a expiré.');
        }

        // Validate
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Create user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $invitation->email,
            'birth_date' => $validated['birth_date'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        // Accept invitation
        $invitation->accept();

        // Update temporary_team_data
        $registration = $invitation->registration;
        $teamData = $registration->temporary_team_data ?? [];

        foreach ($teamData as &$member) {
            if ($member['email'] === $invitation->email) {
                $member['status'] = 'accepted';
                $member['user_id'] = $user->id;
                break;
            }
        }

        $registration->update(['temporary_team_data' => $teamData]);

        // Log in user
        Auth::login($user);

        return redirect()->route('races.show', $registration->race_id)
            ->with('success', 'Compte créé et équipe rejointe avec succès !');
    }
}
