<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckAdminPageAccess;
use App\Models\MedicalDoc;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Unit tests for the CheckAdminPageAccess middleware.
 * 
 * Tests verify the middleware correctly:
 * - Allows admin users to access any page
 * - Allows users with the correct permission to access their pages
 * - Denies access to users without the correct permission
 * - Denies access to unauthenticated users
 */
class CheckAdminPageAccessTest extends TestCase
{
    use RefreshDatabase;

    private CheckAdminPageAccess $middleware;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->middleware = new CheckAdminPageAccess();
        $this->ensureRolesAndPermissionsExist();

        // Clear cache again after setup
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Ensure all required roles and permissions exist.
     */
    private function ensureRolesAndPermissionsExist(): void
    {
        $permissions = [
            'access-admin',
            'access-admin-raids',
            'access-admin-clubs',
            'access-admin-races',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $gestionnaireRaidRole = Role::firstOrCreate(['name' => 'gestionnaire-raid']);
        $gestionnaireRaidRole->givePermissionTo(['access-admin', 'access-admin-raids']);

        $responsableClubRole = Role::firstOrCreate(['name' => 'responsable-club']);
        $responsableClubRole->givePermissionTo(['access-admin', 'access-admin-clubs']);

        $responsableCourseRole = Role::firstOrCreate(['name' => 'responsable-course']);
        $responsableCourseRole->givePermissionTo(['access-admin', 'access-admin-races']);
    }

    /**
     * Create a user with the given role.
     */
    private function createUserWithRole(string $role): User
    {
        $medicalDoc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();
        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
        ]);
        $user->syncRoles([$role]);
        return $user;
    }

    /**
     * Create a mock request with optional user.
     */
    private function createRequest(?User $user = null): Request
    {
        $request = Request::create('/admin/test', 'GET');
        if ($user) {
            $request->setUserResolver(fn () => $user);
        }
        return $request;
    }

    // ==========================================
    // Tests for admin bypass
    // ==========================================

    /**
     * Test admin user bypasses permission check.
     */
    public function test_admin_bypasses_permission_check(): void
    {
        $admin = $this->createUserWithRole('admin');
        $request = $this->createRequest($admin);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'access-admin-raids');

        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test admin can access any admin page regardless of specific permission.
     */
    public function test_admin_can_access_any_permission(): void
    {
        $admin = $this->createUserWithRole('admin');
        $request = $this->createRequest($admin);

        // Test each permission
        $permissions = ['access-admin-raids', 'access-admin-clubs', 'access-admin-races'];
        foreach ($permissions as $permission) {
            $response = $this->middleware->handle($request, fn () => response('OK'), $permission);
            $this->assertEquals('OK', $response->getContent());
        }
    }

    // ==========================================
    // Tests for permission-based access
    // ==========================================

    /**
     * Test user with correct permission can access the page.
     */
    public function test_user_with_permission_can_access(): void
    {
        $user = $this->createUserWithRole('gestionnaire-raid');
        $request = $this->createRequest($user);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'access-admin-raids');

        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test user without permission is denied access.
     */
    public function test_user_without_permission_is_denied(): void
    {
        $user = $this->createUserWithRole('gestionnaire-raid');
        $request = $this->createRequest($user);

        $this->expectException(HttpException::class);
        $this->middleware->handle($request, fn () => response('OK'), 'access-admin-clubs');
    }

    /**
     * Test responsable-club can access clubs but not raids.
     */
    public function test_responsable_club_access(): void
    {
        $user = $this->createUserWithRole('responsable-club');
        $request = $this->createRequest($user);

        // Can access clubs
        $response = $this->middleware->handle($request, fn () => response('OK'), 'access-admin-clubs');
        $this->assertEquals('OK', $response->getContent());

        // Cannot access raids
        $this->expectException(HttpException::class);
        $this->middleware->handle($request, fn () => response('OK'), 'access-admin-raids');
    }

    /**
     * Test responsable-course can access races but not clubs.
     */
    public function test_responsable_course_access(): void
    {
        $user = $this->createUserWithRole('responsable-course');
        $request = $this->createRequest($user);

        // Can access races
        $response = $this->middleware->handle($request, fn () => response('OK'), 'access-admin-races');
        $this->assertEquals('OK', $response->getContent());

        // Cannot access clubs
        $this->expectException(HttpException::class);
        $this->middleware->handle($request, fn () => response('OK'), 'access-admin-clubs');
    }

    // ==========================================
    // Tests for unauthenticated users
    // ==========================================

    /**
     * Test unauthenticated user is denied access.
     */
    public function test_unauthenticated_user_is_denied(): void
    {
        $request = Request::create('/admin/test', 'GET');
        // Don't set any user

        $this->expectException(HttpException::class);
        $this->middleware->handle($request, fn () => response('OK'), 'access-admin-raids');
    }

    // ==========================================
    // Tests for multiple roles
    // ==========================================

    /**
     * Test user with multiple roles has cumulative access.
     */
    public function test_user_with_multiple_roles_has_cumulative_access(): void
    {
        $medicalDoc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();
        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
        ]);
        $user->syncRoles(['gestionnaire-raid', 'responsable-club']);
        
        $request = $this->createRequest($user);

        // Can access raids
        $response = $this->middleware->handle($request, fn () => response('OK'), 'access-admin-raids');
        $this->assertEquals('OK', $response->getContent());

        // Can access clubs
        $response = $this->middleware->handle($request, fn () => response('OK'), 'access-admin-clubs');
        $this->assertEquals('OK', $response->getContent());

        // Cannot access races
        $this->expectException(HttpException::class);
        $this->middleware->handle($request, fn () => response('OK'), 'access-admin-races');
    }
}
