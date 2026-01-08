<?php

namespace Tests\Feature\Admin;

use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Raid;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use App\Models\ParamType;
use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite for role-based admin page access.
 * 
 * Tests verify that:
 * - gestionnaire-raid can only access /admin/raids
 * - responsable-club can only access /admin/clubs
 * - responsable-course can only access /admin/races
 * - Multiple roles grant cumulative access
 * - Admin users can access all admin pages
 */
class AdminPageAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $gestionnaireRaidUser;
    private User $responsableClubUser;
    private User $responsableCourseUser;
    private User $multiRoleUser;
    private User $regularUser;

    /**
     * Setup test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->ensureRolesAndPermissionsExist();

        // Clear cache again after setup
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createTestUsers();
    }

    /**
     * Ensure all required roles and permissions exist.
     */
    private function ensureRolesAndPermissionsExist(): void
    {
        // Create admin page access permissions
        $permissions = [
            'access-admin',
            'access-admin-raids',
            'access-admin-clubs',
            'access-admin-races',
            'view users',
            'edit users',
            'delete users',
            'view logs',
            'grant role',
            'grant admin',
            'accept-club',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $userRole = Role::firstOrCreate(['name' => 'user']);

        $gestionnaireRaidRole = Role::firstOrCreate(['name' => 'gestionnaire-raid']);
        $gestionnaireRaidRole->givePermissionTo(['access-admin', 'access-admin-raids']);

        $responsableClubRole = Role::firstOrCreate(['name' => 'responsable-club']);
        $responsableClubRole->givePermissionTo(['access-admin', 'access-admin-clubs']);

        $responsableCourseRole = Role::firstOrCreate(['name' => 'responsable-course']);
        $responsableCourseRole->givePermissionTo(['access-admin', 'access-admin-races']);
    }

    /**
     * Create test users with different roles.
     */
    private function createTestUsers(): void
    {
        // Create medical doc and member for users
        $medicalDoc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();

        // Admin user
        $this->adminUser = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);

        // Gestionnaire Raid user
        $medicalDoc2 = MedicalDoc::factory()->create();
        $member2 = Member::factory()->create();
        $this->gestionnaireRaidUser = User::factory()->create([
            'adh_id' => $member2->adh_id,
            'doc_id' => $medicalDoc2->doc_id,
        ]);
        $this->gestionnaireRaidUser->syncRoles(['gestionnaire-raid']);

        // Responsable Club user
        $medicalDoc3 = MedicalDoc::factory()->create();
        $member3 = Member::factory()->create();
        $this->responsableClubUser = User::factory()->create([
            'adh_id' => $member3->adh_id,
            'doc_id' => $medicalDoc3->doc_id,
        ]);
        $this->responsableClubUser->syncRoles(['responsable-club']);

        // Responsable Course user
        $medicalDoc4 = MedicalDoc::factory()->create();
        $member4 = Member::factory()->create();
        $this->responsableCourseUser = User::factory()->create([
            'adh_id' => $member4->adh_id,
            'doc_id' => $medicalDoc4->doc_id,
        ]);
        $this->responsableCourseUser->syncRoles(['responsable-course']);

        // Multi-role user (gestionnaire-raid + responsable-club)
        $medicalDoc5 = MedicalDoc::factory()->create();
        $member5 = Member::factory()->create();
        $this->multiRoleUser = User::factory()->create([
            'adh_id' => $member5->adh_id,
            'doc_id' => $medicalDoc5->doc_id,
        ]);
        $this->multiRoleUser->syncRoles(['gestionnaire-raid', 'responsable-club']);

        // Regular user without admin access
        $medicalDoc6 = MedicalDoc::factory()->create();
        $member6 = Member::factory()->create();
        $this->regularUser = User::factory()->create([
            'adh_id' => $member6->adh_id,
            'doc_id' => $medicalDoc6->doc_id,
        ]);
        $this->regularUser->syncRoles(['user']);
    }

    // ==========================================
    // Tests for gestionnaire-raid role
    // ==========================================

    /**
     * Test gestionnaire-raid can access /admin/raids.
     */
    public function test_gestionnaire_raid_can_access_admin_raids(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)->get('/admin/raids');
        $response->assertStatus(200);
    }

    /**
     * Test gestionnaire-raid cannot access /admin/clubs.
     */
    public function test_gestionnaire_raid_cannot_access_admin_clubs(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)->get('/admin/clubs');
        $response->assertStatus(403);
    }

    /**
     * Test gestionnaire-raid cannot access /admin/races.
     */
    public function test_gestionnaire_raid_cannot_access_admin_races(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)->get('/admin/races');
        $response->assertStatus(403);
    }

    // ==========================================
    // Tests for responsable-club role
    // ==========================================

    /**
     * Test responsable-club can access /admin/clubs.
     */
    public function test_responsable_club_can_access_admin_clubs(): void
    {
        $response = $this->actingAs($this->responsableClubUser)->get('/admin/clubs');
        $response->assertStatus(200);
    }

    /**
     * Test responsable-club cannot access /admin/raids.
     */
    public function test_responsable_club_cannot_access_admin_raids(): void
    {
        $response = $this->actingAs($this->responsableClubUser)->get('/admin/raids');
        $response->assertStatus(403);
    }

    /**
     * Test responsable-club cannot access /admin/races.
     */
    public function test_responsable_club_cannot_access_admin_races(): void
    {
        $response = $this->actingAs($this->responsableClubUser)->get('/admin/races');
        $response->assertStatus(403);
    }

    // ==========================================
    // Tests for responsable-course role
    // ==========================================

    /**
     * Test responsable-course can access /admin/races.
     */
    public function test_responsable_course_can_access_admin_races(): void
    {
        $response = $this->actingAs($this->responsableCourseUser)->get('/admin/races');
        $response->assertStatus(200);
    }

    /**
     * Test responsable-course cannot access /admin/raids.
     */
    public function test_responsable_course_cannot_access_admin_raids(): void
    {
        $response = $this->actingAs($this->responsableCourseUser)->get('/admin/raids');
        $response->assertStatus(403);
    }

    /**
     * Test responsable-course cannot access /admin/clubs.
     */
    public function test_responsable_course_cannot_access_admin_clubs(): void
    {
        $response = $this->actingAs($this->responsableCourseUser)->get('/admin/clubs');
        $response->assertStatus(403);
    }

    // ==========================================
    // Tests for cumulative role access
    // ==========================================

    /**
     * Test user with multiple roles can access multiple admin pages.
     * User has gestionnaire-raid + responsable-club roles.
     */
    public function test_multi_role_user_can_access_raids_and_clubs(): void
    {
        // Can access raids
        $response = $this->actingAs($this->multiRoleUser)->get('/admin/raids');
        $response->assertStatus(200);

        // Can access clubs
        $response = $this->actingAs($this->multiRoleUser)->get('/admin/clubs');
        $response->assertStatus(200);
    }

    /**
     * Test user with multiple roles still cannot access pages without permission.
     */
    public function test_multi_role_user_cannot_access_unauthorized_pages(): void
    {
        // Cannot access races (doesn't have responsable-course role)
        $response = $this->actingAs($this->multiRoleUser)->get('/admin/races');
        $response->assertStatus(403);
    }

    // ==========================================
    // Tests for admin role
    // ==========================================

    /**
     * Test admin can access all admin pages.
     */
    public function test_admin_can_access_all_admin_pages(): void
    {
        // Can access raids
        $response = $this->actingAs($this->adminUser)->get('/admin/raids');
        $response->assertStatus(200);

        // Can access clubs
        $response = $this->actingAs($this->adminUser)->get('/admin/clubs');
        $response->assertStatus(200);

        // Can access races
        $response = $this->actingAs($this->adminUser)->get('/admin/races');
        $response->assertStatus(200);

        // Can access dashboard
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
    }

    // ==========================================
    // Tests for regular user (no access)
    // ==========================================

    /**
     * Test regular user cannot access admin dashboard.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin');
        $response->assertStatus(403);
    }

    /**
     * Test regular user cannot access /admin/raids.
     */
    public function test_regular_user_cannot_access_admin_raids(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/raids');
        $response->assertStatus(403);
    }

    /**
     * Test regular user cannot access /admin/clubs.
     */
    public function test_regular_user_cannot_access_admin_clubs(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/clubs');
        $response->assertStatus(403);
    }

    /**
     * Test regular user cannot access /admin/races.
     */
    public function test_regular_user_cannot_access_admin_races(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/races');
        $response->assertStatus(403);
    }

    // ==========================================
    // Tests for unauthenticated users
    // ==========================================

    /**
     * Test unauthenticated user is redirected from admin pages.
     */
    public function test_unauthenticated_user_redirected_from_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/raids');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/clubs');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/races');
        $response->assertRedirect('/login');
    }

    // ==========================================
    // Tests for all three roles combined
    // ==========================================

    /**
     * Test user with all three management roles can access all management pages.
     */
    public function test_user_with_all_management_roles_can_access_all_management_pages(): void
    {
        // Create user with all three roles
        $medicalDoc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();
        $allRolesUser = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
        ]);
        $allRolesUser->syncRoles(['gestionnaire-raid', 'responsable-club', 'responsable-course']);

        // Can access all management pages
        $response = $this->actingAs($allRolesUser)->get('/admin/raids');
        $response->assertStatus(200);

        $response = $this->actingAs($allRolesUser)->get('/admin/clubs');
        $response->assertStatus(200);

        $response = $this->actingAs($allRolesUser)->get('/admin/races');
        $response->assertStatus(200);
    }
}
