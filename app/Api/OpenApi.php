<?php

namespace App\Api;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="SAER301 API Documentation",
 *      description="Auto-generated API documentation for SAE301 project",
 *      @OA\Contact(
 *          email="admin@example.com"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Production API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with email and password to get the authentication token",
 *     name="Token based Based",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="apiAuth",
 * )
 * 
 * @OA\Tag(
 *     name="Admin - Club Approval",
 *     description="Admin endpoints for club approval management"
 * )
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints for login and registration"
 * )
 * @OA\Tag(
 *     name="Clubs",
 *     description="Club management endpoints"
 * )
 * @OA\Tag(
 *     name="Club Members",
 *     description="Club member management endpoints"
 * )
 * @OA\Tag(
 *     name="Leaderboard",
 *     description="API endpoints for leaderboard management"
 * )
 * @OA\Tag(
 *     name="Profile",
 *     description="User profile management endpoints"
 * )
 * @OA\Tag(
 *     name="Races",
 *     description="Public race API endpoints"
 * )
 * @OA\Tag(
 *     name="Race Management",
 *     description="Endpoints for managing races"
 * )
 * @OA\Tag(
 *     name="Race Participants",
 *     description="Endpoints for managing runners in race registrations and their PPS information"
 * )
 * @OA\Tag(
 *     name="Raids",
 *     description="Public raids API endpoints"
 * )
 * @OA\Tag(
 *     name="Teams",
 *     description="Team creation, management, and invitation endpoints"
 * )
 * @OA\Tag(
 *     name="Team Age Validation",
 *     description="Endpoints for validating team age composition and participant eligibility"
 * )
 * @OA\Tag(
 *     name="Team Management",
 *     description="Endpoints for team management (admin and team leaders)"
 * )
 * @OA\Tag(
 *     name="User",
 *     description="User profile and account management endpoints"
 * )
 */


class OpenApi {}