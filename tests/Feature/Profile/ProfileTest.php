<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\FanProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_gets_authenticated_user_profile()
    {
        $user = User::factory()->create();
        FanProfile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'avatar',
                    'role',
                    'locale',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_profile_access()
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_updates_profile_name()
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function it_updates_profile_locale()
    {
        $user = User::factory()->create(['locale' => 'en']);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'locale' => 'ar',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'locale' => 'ar',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'ar',
        ]);
    }

    /** @test */
    public function it_uploads_avatar_successfully()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['avatar_url'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify file was stored
        Storage::disk('public')->assertExists('avatars/' . $file->hashName());
    }

    /** @test */
    public function it_returns_422_for_invalid_avatar_file()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['avatar'],
            ]);
    }

    /** @test */
    public function it_changes_password_successfully()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/change-password', [
            'current_password' => 'old_password',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }

    /** @test */
    public function it_returns_422_for_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct_password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/change-password', [
            'current_password' => 'wrong_password',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_deletes_account_successfully()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/profile', [
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function it_returns_422_for_delete_without_password()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/profile');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_updates_multiple_profile_fields()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'locale' => 'en',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'name' => 'Updated Name',
            'locale' => 'ar',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'locale' => 'ar',
                ],
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_locale()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'locale' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['locale'],
            ]);
    }
}
