<?php

namespace App\Http\Controllers;

use App\Services\AgeValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use OpenApi\Annotations as OA;

/**
 * Controller for team age validation.
 * 
 * Handles validation of team compositions based on age requirements.
 */
class TeamAgeController extends Controller
{
    /**
     * The age validation service instance.
     */
    protected AgeValidationService $ageService;

    /**
     * Create a new controller instance.
     *
     * @param AgeValidationService $ageService
     */
    public function __construct(AgeValidationService $ageService)
    {
        $this->ageService = $ageService;
    }

    /**
     * Display the team age validation page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Team/AgeValidation', [
            'thresholds' => $this->ageService->getThresholds(),
            'rules' => $this->ageService->getRulesExplanation(),
        ]);
    }

    /**
     * Get the current age thresholds.
     *
     * @OA\Get(
     *     path="/api/team/age-thresholds",
     *     tags={"Team"},
     *     summary="Get age thresholds",
     *     description="Returns the current age thresholds (A, B, C) for team validation",
     *     @OA\Response(
     *         response=200,
     *         description="Age thresholds retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="thresholds", type="object",
     *                 @OA\Property(property="min", type="integer", example=12),
     *                 @OA\Property(property="intermediate", type="integer", example=16),
     *                 @OA\Property(property="adult", type="integer", example=18)
     *             ),
     *             @OA\Property(property="rules", type="string", example="Age requirements: All participants must be at least 12 years old...")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function getThresholds(): JsonResponse
    {
        return response()->json([
            'thresholds' => $this->ageService->getThresholds(),
            'rules' => $this->ageService->getRulesExplanation(),
        ]);
    }

    /**
     * Validate a team's age composition.
     *
     * @OA\Post(
     *     path="/api/team/validate-ages",
     *     tags={"Team"},
     *     summary="Validate team ages",
     *     description="Validates a team's age composition against the rules: all >= A, and if any < B then at least one >= C",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="ages",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={14, 15, 20}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Validation result",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="details", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid input"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateAges(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ages' => 'required|array|min:1',
            'ages.*' => 'required|integer|min:0|max:150',
        ]);

        $result = $this->ageService->validateTeam($validated['ages']);

        return response()->json($result);
    }

    /**
     * Validate a team using birthdates.
     *
     * @OA\Post(
     *     path="/api/team/validate-birthdates",
     *     tags={"Team"},
     *     summary="Validate team by birthdates",
     *     description="Validates a team's composition by calculating ages from birthdates",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="birthdates",
     *                 type="array",
     *                 @OA\Items(type="string", format="date"),
     *                 example={"2010-05-15", "2009-08-22", "2004-01-10"}
     *             ),
     *             @OA\Property(
     *                 property="reference_date",
     *                 type="string",
     *                 format="date",
     *                 nullable=true,
     *                 description="Date to calculate ages from (defaults to today)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Validation result",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="details", type="object"),
     *             @OA\Property(property="calculated_ages", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid input"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateBirthdates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'birthdates' => 'required|array|min:1',
            'birthdates.*' => 'required|date|before_or_equal:today',
            'reference_date' => 'nullable|date',
        ]);

        $referenceDate = $validated['reference_date'] ?? null;
        
        // Calculate ages for response
        $calculatedAges = array_map(
            fn($birthdate) => $this->ageService->calculateAge($birthdate, $referenceDate),
            $validated['birthdates']
        );

        $result = $this->ageService->validateTeamByBirthdates(
            $validated['birthdates'],
            $referenceDate
        );

        $result['calculated_ages'] = $calculatedAges;

        return response()->json($result);
    }

    /**
     * Check if a single participant meets the minimum age requirement.
     *
     * @OA\Post(
     *     path="/api/team/check-participant",
     *     tags={"Team"},
     *     summary="Check participant eligibility",
     *     description="Checks if a single participant meets the minimum age requirement",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="age", type="integer", example=14)
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="birthdate", type="string", format="date", example="2010-05-15")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Eligibility result",
     *         @OA\JsonContent(
     *             @OA\Property(property="eligible", type="boolean"),
     *             @OA\Property(property="age", type="integer"),
     *             @OA\Property(property="is_minor", type="boolean"),
     *             @OA\Property(property="is_adult", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkParticipant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'age' => 'required_without:birthdate|integer|min:0|max:150',
            'birthdate' => 'required_without:age|date|before_or_equal:today',
        ]);

        $age = $validated['age'] ?? $this->ageService->calculateAge($validated['birthdate']);
        
        $thresholds = $this->ageService->getThresholds();
        $eligible = $this->ageService->isParticipantValid($age);
        $isMinor = $this->ageService->isMinor($age);
        $isAdult = $this->ageService->isAdult($age);

        $message = $eligible 
            ? ($isMinor 
                ? "Participant is eligible but under {$thresholds['intermediate']}. Team will need an adult ({$thresholds['adult']}+)."
                : ($isAdult 
                    ? "Participant is eligible and can supervise minors."
                    : "Participant is eligible."))
            : "Participant must be at least {$thresholds['min']} years old.";

        return response()->json([
            'eligible' => $eligible,
            'age' => $age,
            'is_minor' => $isMinor,
            'is_adult' => $isAdult,
            'message' => $message,
        ]);
    }
}
