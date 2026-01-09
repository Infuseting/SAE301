<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates a test admin user for development/testing purposes.
     * Email: admin@test.fr
     * Password: password
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create admin test user if it doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'admin@test.fr'],
            [
                'name' => 'Admin Test',
                'last_name' => 'Test',
                'first_name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        $user->assignRole('admin');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the test admin user
        User::where('email', 'admin@test.fr')->delete();
    }
};
