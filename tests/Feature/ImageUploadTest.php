<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Raid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test suite for Image Upload functionality
 * 
 * Tests cover:
 * - Image upload during raid creation
 * - Image upload during raid update
 * - File validation (type, size)
 * - Image storage and retrieval
 * - Image deletion
 * - Error handling
 */
class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $clubLeader;
    private Club $club;
    private Member $member;
    private array $validRaidData;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configure fake storage
        Storage::fake('public');

        // Create medical document
        $medicalDoc = MedicalDoc::factory()->create();

        // Create member
        $this->member = Member::factory()->create();

        // Create club leader user with member
        $this->clubLeader = User::factory()->create([
            'doc_id' => $medicalDoc->doc_id,
            'adh_id' => $this->member->adh_id,
        ]);

        // Assign responsable-club role
        $this->clubLeader->assignRole('responsable-club');

        // Create club owned by the leader
        $this->club = Club::factory()->create([
            'created_by' => $this->clubLeader->id,
        ]);

        // Add leader to club as manager
        $this->club->allMembers()->attach($this->clubLeader->id, [
            'role' => 'manager',
            'status' => 'approved',
        ]);

        // Valid raid data for testing
        $this->validRaidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Test description',
            'raid_date_start' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'raid_date_end' => now()->addDays(32)->format('Y-m-d H:i:s'),
            'ins_start_date' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'ins_end_date' => now()->addDays(29)->format('Y-m-d H:i:s'),
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Test Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'adh_id' => $this->member->adh_id,
            'clu_id' => $this->club->club_id,
        ];
    }

    /**
     * Test 1: Valid image upload during raid creation
     */
    public function test_can_upload_valid_image_during_raid_creation(): void
    {
        $image = UploadedFile::fake()->create('raid-image.jpg', 1000, 'image/jpeg');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertRedirect(route('raids.index'));

        // Verify image was stored
        Storage::disk('public')->assertExists('raids/' . $image->hashName());

        // Verify database entry
        $raid = Raid::latest()->first();
        $this->assertNotNull($raid->raid_image);
        $this->assertStringContainsString('raids/', $raid->raid_image);
    }

    /**
     * Test 2: Raid creation without image (optional field)
     */
    public function test_can_create_raid_without_image(): void
    {
        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $this->validRaidData);

        $response->assertRedirect(route('raids.index'));

        $raid = Raid::latest()->first();
        $this->assertNull($raid->raid_image);
    }

    /**
     * Test 3: PNG image upload
     */
    public function test_can_upload_png_image(): void
    {
        $image = UploadedFile::fake()->create('raid-image.png', 1000, 'image/png');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertRedirect(route('raids.index'));
        Storage::disk('public')->assertExists('raids/' . $image->hashName());
    }

    /**
     * Test 4: GIF image upload
     */
    public function test_can_upload_gif_image(): void
    {
        $image = UploadedFile::fake()->create('raid-image.gif', 1000, 'image/gif');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertRedirect(route('raids.index'));
        Storage::disk('public')->assertExists('raids/' . $image->hashName());
    }

    /**
     * Test 5: WebP image upload
     */
    public function test_can_upload_webp_image(): void
    {
        // Create a fake WebP file
        $image = UploadedFile::fake()->create('raid-image.webp', 500, 'image/webp');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertRedirect(route('raids.index'));
        Storage::disk('public')->assertExists('raids/' . $image->hashName());
    }

    /**
     * Test 6: Reject non-image files
     */
    public function test_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $file,
            ]));

        $response->assertSessionHasErrors('raid_image');
        Storage::disk('public')->assertMissing('raids/' . $file->hashName());
    }

    /**
     * Test 7: Reject oversized images (> 5MB)
     */
    public function test_rejects_oversized_images(): void
    {
        // Create a 6MB file
        $image = UploadedFile::fake()->create('large-image.jpg', 6144, 'image/jpeg');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertSessionHasErrors('raid_image');
        Storage::disk('public')->assertMissing('raids/' . $image->hashName());
    }

    /**
     * Test 8: Accept maximum allowed size (5MB)
     */
    public function test_accepts_max_size_image(): void
    {
        // Create exactly 5MB file
        $image = UploadedFile::fake()->create('max-size-image.jpg', 5120, 'image/jpeg');

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertRedirect(route('raids.index'));
        Storage::disk('public')->assertExists('raids/' . $image->hashName());
    }

    /**
     * Test 9: Update raid with new image
     */
    public function test_can_update_raid_with_new_image(): void
    {
        // Create raid without image
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->member->adh_id,
            'raid_image' => null,
        ]);

        $newImage = UploadedFile::fake()->create('new-image.jpg', 1000, 'image/jpeg');

        // Build update data from existing raid
        $updateData = [
            'raid_name' => $raid->raid_name,
            'raid_description' => $raid->raid_description,
            'raid_date_start' => $raid->raid_date_start->format('Y-m-d H:i:s'),
            'raid_date_end' => $raid->raid_date_end->format('Y-m-d H:i:s'),
            'ins_start_date' => $raid->registrationPeriod->ins_start_date->format('Y-m-d H:i:s'),
            'ins_end_date' => $raid->registrationPeriod->ins_end_date->format('Y-m-d H:i:s'),
            'raid_contact' => $raid->raid_contact,
            'raid_street' => $raid->raid_street,
            'raid_city' => $raid->raid_city,
            'raid_postal_code' => $raid->raid_postal_code,
            'raid_number' => $raid->raid_number,
            'adh_id' => $raid->adh_id,
            'clu_id' => $raid->clu_id,
            'raid_image' => $newImage,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->patch(route('raids.update', $raid->raid_id), $updateData);

        $response->assertRedirect();

        $raid->refresh();
        $this->assertNotNull($raid->raid_image);
        Storage::disk('public')->assertExists('raids/' . $newImage->hashName());
    }

    /**
     * Test 10: Replace existing image with new one
     */
    public function test_can_replace_existing_image(): void
    {
        // Create raid with existing image
        $oldImage = UploadedFile::fake()->create('old-image.jpg', 1000, 'image/jpeg');
        Storage::disk('public')->put('raids/' . $oldImage->hashName(), file_get_contents($oldImage));

        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->member->adh_id,
            'raid_image' => 'raids/' . $oldImage->hashName(),
        ]);

        $newImage = UploadedFile::fake()->create('new-image.jpg', 1000, 'image/jpeg');

        // Build update data from existing raid
        $updateData = [
            'raid_name' => $raid->raid_name,
            'raid_description' => $raid->raid_description,
            'raid_date_start' => $raid->raid_date_start->format('Y-m-d H:i:s'),
            'raid_date_end' => $raid->raid_date_end->format('Y-m-d H:i:s'),
            'ins_start_date' => $raid->registrationPeriod->ins_start_date->format('Y-m-d H:i:s'),
            'ins_end_date' => $raid->registrationPeriod->ins_end_date->format('Y-m-d H:i:s'),
            'raid_contact' => $raid->raid_contact,
            'raid_street' => $raid->raid_street,
            'raid_city' => $raid->raid_city,
            'raid_postal_code' => $raid->raid_postal_code,
            'raid_number' => $raid->raid_number,
            'adh_id' => $raid->adh_id,
            'clu_id' => $raid->clu_id,
            'raid_image' => $newImage,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->patch(route('raids.update', $raid->raid_id), $updateData);

        $response->assertRedirect();

        $raid->refresh();
        $this->assertNotNull($raid->raid_image);
        $this->assertStringContainsString($newImage->hashName(), $raid->raid_image);
        Storage::disk('public')->assertExists('raids/' . $newImage->hashName());
    }

    /**
     * Test 11: Multiple raids can have different images
     */
    public function test_multiple_raids_can_have_different_images(): void
    {
        $image1 = UploadedFile::fake()->create('raid1.jpg', 1000, 'image/jpeg');
        $image2 = UploadedFile::fake()->create('raid2.jpg', 1000, 'image/jpeg');

        // Create first raid
        $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_name' => 'Raid 1',
                'raid_image' => $image1,
            ]));

        // Create second raid
        $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_name' => 'Raid 2',
                'raid_number' => 2026002,
                'raid_image' => $image2,
            ]));

        $this->assertCount(2, Raid::all());
        Storage::disk('public')->assertExists('raids/' . $image1->hashName());
        Storage::disk('public')->assertExists('raids/' . $image2->hashName());
    }

    /**
     * Test 12: Unauthenticated users cannot upload images
     */
    public function test_unauthenticated_users_cannot_upload_images(): void
    {
        $image = UploadedFile::fake()->create('raid-image.jpg', 1000, 'image/jpeg');

        $response = $this->post(route('raids.store'), array_merge($this->validRaidData, [
            'raid_image' => $image,
        ]));

        $response->assertRedirect(route('login'));
        Storage::disk('public')->assertMissing('raids/' . $image->hashName());
    }

    /**
     * Test 13: Non-club leaders cannot upload images
     */
    public function test_non_club_leaders_cannot_upload_images(): void
    {
        $regularUser = User::factory()->create();
        $image = UploadedFile::fake()->create('raid-image.jpg', 1000, 'image/jpeg');

        $response = $this->actingAs($regularUser)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $response->assertForbidden();
        Storage::disk('public')->assertMissing('raids/' . $image->hashName());
    }

    /**
     * Test 14: Image path stored correctly in database
     */
    public function test_image_path_stored_correctly_in_database(): void
    {
        $image = UploadedFile::fake()->create('test.jpg', 1000, 'image/jpeg');

        $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $raid = Raid::latest()->first();
        
        // Path should be relative (raids/filename.jpg)
        $this->assertStringStartsWith('raids/', $raid->raid_image);
        $this->assertStringEndsWith('.jpg', $raid->raid_image);
    }

    /**
     * Test 15: Image accessible via storage link
     */
    public function test_image_accessible_via_storage_link(): void
    {
        $image = UploadedFile::fake()->create('accessible.jpg', 1000, 'image/jpeg');

        $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), array_merge($this->validRaidData, [
                'raid_image' => $image,
            ]));

        $raid = Raid::latest()->first();
        
        // Verify the path can be used with /storage/ prefix
        $publicPath = '/storage/' . $raid->raid_image;
        $this->assertStringContainsString('/storage/raids/', $publicPath);
    }
}
