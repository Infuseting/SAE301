<?php

namespace Tests\Unit\PermissionsRoles;

use App\Models\Club;
use App\Models\Race;
use App\Models\Raid;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\User;
use App\Policies\ClubPolicy;
use App\Policies\RaidPolicy;
use App\Policies\RacePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Unit tests for Policy methods
 * 
 * Tests policy authorization methods directly without HTTP requests:
 * - ClubPolicy methods
 * - RaidPolicy methods
 * - RacePolicy methods
 */
class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $guestUser;
    private User $regularUser;
    private User $responsableClubUser;
    private User $responsableCourseUser;
    private User $adminUser;
    private ClubPolicy $clubPolicy;
    private RaidPolicy $raidPolicy;
    private RacePolicy $racePolicy;
    private int $clubId;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRolesAndPermissionsExist();

        // Create guest user
        $this->guestUser = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);
        $this->guestUser->syncRoles(['guest']);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);
        $this->regularUser->syncRoles(['user']);

        // Create responsable-club user
        $responsableDoc = MedicalDoc::factory()->create();
        $responsableMember = Member::factory()->create();
        $this->responsableClubUser = User::factory()->create([
            'adh_id' => $responsableMember->adh_id,
            'doc_id' => $responsableDoc->doc_id,
        ]);
        $this->responsableClubUser->syncRoles(['responsable-club']);

        // Create an approved club for responsable-club
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFCO999',
            'description' => 'Test Club Description',
            'is_approved' => true,
            'created_by' => $this->responsableClubUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->responsableClubUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create responsable-course user
        $courseDoc = MedicalDoc::factory()->create();
        $courseMember = Member::factory()->create();
        $this->responsableCourseUser = User::factory()->create([
            'adh_id' => $courseMember->adh_id,
            'doc_id' => $courseDoc->doc_id,
        ]);
        $this->responsableCourseUser->syncRoles(['responsable-course']);

        // Create admin user
        $adminDoc = MedicalDoc::factory()->create();
        $adminMember = Member::factory()->create();
        $this->adminUser = User::factory()->create([
            'adh_id' => $adminMember->adh_id,
            'doc_id' => $adminDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);

        // Initialize policies
        $this->clubPolicy = new ClubPolicy();
        $this->raidPolicy = new RaidPolicy();
        $this->racePolicy = new RacePolicy();
    }

    /**
     * Ensure all required roles and permissions exist
     */
    private function ensureRolesAndPermissionsExist(): void
    {
        $roles = ['guest', 'user', 'adherent', 'responsable-club', 'gestionnaire-raid', 'responsable-course', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            'create-club', 'edit-own-club', 'delete-own-club', 'view-clubs',
            'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids',
            'create-race', 'edit-own-race', 'delete-own-race', 'view-races',
            'manage-all-raids', 'manage-all-clubs', 'manage-all-races', 'access-admin',
            'register-to-race'
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $responsableClubRole = Role::findByName('responsable-club');
        $responsableClubRole->syncPermissions(['create-club', 'edit-own-club', 'delete-own-club', 'view-clubs', 'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids']);

        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->syncPermissions(['create-race', 'edit-own-race', 'delete-own-race', 'view-races', 'view-raids', 'view-clubs', 'register-to-race']);

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }

    // ===========================================
    // CLUB POLICY TESTS
    // ===========================================

    /**
     * Test ClubPolicy::viewAny allows everyone
     */
    public function test_club_policy_view_any_allows_everyone(): void
    {
        $this->assertTrue($this->clubPolicy->viewAny($this->guestUser));
        $this->assertTrue($this->clubPolicy->viewAny($this->regularUser));
        $this->assertTrue($this->clubPolicy->viewAny($this->adminUser));
    }

    /**
     * Test ClubPolicy::create - guest cannot create
     */
    public function test_club_policy_guest_cannot_create(): void
    {
        $this->assertFalse($this->clubPolicy->create($this->guestUser));
    }

    /**
     * Test ClubPolicy::create - regular user cannot create
     */
    public function test_club_policy_regular_user_cannot_create(): void
    {
        $this->assertFalse($this->clubPolicy->create($this->regularUser));
    }

    /**
     * Test ClubPolicy::create - responsable-club cannot create (only adherent and admin can)
     */
    public function test_club_policy_responsable_club_can_create(): void
    {
        // Note: ClubPolicy now only allows adherent and admin to create clubs
        // responsable-club without adherent role cannot create
        $this->assertFalse($this->clubPolicy->create($this->responsableClubUser));
    }

    // ===========================================
    // RAID POLICY TESTS
    // ===========================================

    /**
     * Test RaidPolicy::viewAny allows everyone
     */
    public function test_raid_policy_view_any_allows_everyone(): void
    {
        $this->assertTrue($this->raidPolicy->viewAny($this->guestUser));
        $this->assertTrue($this->raidPolicy->viewAny($this->regularUser));
        $this->assertTrue($this->raidPolicy->viewAny($this->adminUser));
    }

    /**
     * Test RaidPolicy::create - guest cannot create
     */
    public function test_raid_policy_guest_cannot_create(): void
    {
        $this->assertFalse($this->raidPolicy->create($this->guestUser));
    }

    /**
     * Test RaidPolicy::create - regular user cannot create
     */
    public function test_raid_policy_regular_user_cannot_create(): void
    {
        $this->assertFalse($this->raidPolicy->create($this->regularUser));
    }

    /**
     * Test RaidPolicy::create - responsable-club with approved club can create
     */
    public function test_raid_policy_responsable_club_can_create(): void
    {
        $this->assertTrue($this->raidPolicy->create($this->responsableClubUser));
    }

    /**
     * Test RaidPolicy::create - admin can always create
     */
    public function test_raid_policy_admin_can_create(): void
    {
        $this->assertTrue($this->raidPolicy->create($this->adminUser));
    }

    // ===========================================
    // RACE POLICY TESTS
    // ===========================================

    /**
     * Test RacePolicy::viewAny allows everyone
     */
    public function test_race_policy_view_any_allows_everyone(): void
    {
        $this->assertTrue($this->racePolicy->viewAny(null));
        $this->assertTrue($this->racePolicy->viewAny($this->guestUser));
        $this->assertTrue($this->racePolicy->viewAny($this->regularUser));
        $this->assertTrue($this->racePolicy->viewAny($this->adminUser));
    }

    /**
     * Test RacePolicy::create - guest cannot create
     */
    public function test_race_policy_guest_cannot_create(): void
    {
        $this->assertFalse($this->racePolicy->create($this->guestUser));
    }

    /**
     * Test RacePolicy::create - regular user cannot create
     */
    public function test_race_policy_regular_user_cannot_create(): void
    {
        $this->assertFalse($this->racePolicy->create($this->regularUser));
    }

    /**
     * Test RacePolicy::create - responsable-club (without course role) cannot create
     */
    public function test_race_policy_responsable_club_cannot_create_without_course_role(): void
    {
        $this->assertFalse($this->racePolicy->create($this->responsableClubUser));
    }

    /**
     * Test RacePolicy::create - responsable-course can create
     */
    public function test_race_policy_responsable_course_can_create(): void
    {
        $this->assertTrue($this->racePolicy->create($this->responsableCourseUser));
    }

    // ===========================================
    // ADMIN BYPASS TESTS
    // ===========================================

    /**
     * Test that admin passes viewAny policy checks
     */
    public function test_admin_passes_view_any_policies(): void
    {
        $this->assertTrue($this->clubPolicy->viewAny($this->adminUser));
        $this->assertTrue($this->raidPolicy->viewAny($this->adminUser));
        $this->assertTrue($this->racePolicy->viewAny($this->adminUser));
    }

    /**
     * Test that admin passes raid create policy
     */
    public function test_admin_passes_raid_create_policy(): void
    {
        $this->assertTrue($this->raidPolicy->create($this->adminUser));
    }

    /**
     * Test guest user has minimal policy access
     */
    public function test_guest_has_view_only_access(): void
    {
        // Guest can view but not create
        $this->assertTrue($this->clubPolicy->viewAny($this->guestUser));
        $this->assertFalse($this->clubPolicy->create($this->guestUser));

        $this->assertTrue($this->raidPolicy->viewAny($this->guestUser));
        $this->assertFalse($this->raidPolicy->create($this->guestUser));

        $this->assertTrue($this->racePolicy->viewAny($this->guestUser));
        $this->assertFalse($this->racePolicy->create($this->guestUser));
    }
}
