<?php

namespace Tests\Feature\Matches;

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedMatchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_saves_a_match_for_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $match = GameMatch::factory()->create(['is_published' => true]);

        $response = $this->postJson("/api/v1/matches/{$match->id}/save");

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Match saved successfully',
                'data' => ['is_saved' => true],
            ]);

        $this->assertDatabaseHas('saved_matches', [
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
    }

    /** @test */
    public function it_unsaves_a_match_when_already_saved()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $match = GameMatch::factory()->create(['is_published' => true]);
        $user->savedMatches()->attach($match->id);

        $response = $this->postJson("/api/v1/matches/{$match->id}/save");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Match unsaved successfully',
                'data' => ['is_saved' => false],
            ]);

        $this->assertDatabaseMissing('saved_matches', [
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_saving_nonexistent_match()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/matches/99999/save');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Match not found',
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_save_a_match()
    {
        $match = GameMatch::factory()->create(['is_published' => true]);

        $response = $this->postJson("/api/v1/matches/{$match->id}/save");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_lists_saved_matches_for_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $matches = GameMatch::factory()->count(3)->create(['is_published' => true]);
        $user->savedMatches()->attach($matches->pluck('id'));

        // Create another match that is NOT saved
        GameMatch::factory()->create(['is_published' => true]);

        $response = $this->getJson('/api/v1/matches/saved');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Saved matches retrieved successfully',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_empty_list_when_no_saved_matches()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/matches/saved');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);

        $this->assertCount(0, $response->json('data'));
    }

    /** @test */
    public function it_requires_authentication_to_list_saved_matches()
    {
        $response = $this->getJson('/api/v1/matches/saved');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_includes_is_saved_flag_in_match_resource()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $match = GameMatch::factory()->create(['is_published' => true]);
        $user->savedMatches()->attach($match->id);

        $unsavedMatch = GameMatch::factory()->create(['is_published' => true]);

        $response = $this->getJson('/api/v1/matches');

        $response->assertStatus(200);

        $data = collect($response->json('data'));
        $savedData = $data->firstWhere('id', $match->id);
        $unsavedData = $data->firstWhere('id', $unsavedMatch->id);

        $this->assertTrue($savedData['is_saved']);
        $this->assertFalse($unsavedData['is_saved']);
    }

    /** @test */
    public function it_only_lists_published_saved_matches()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $publishedMatch = GameMatch::factory()->create(['is_published' => true]);
        $unpublishedMatch = GameMatch::factory()->create(['is_published' => false]);

        $user->savedMatches()->attach([$publishedMatch->id, $unpublishedMatch->id]);

        $response = $this->getJson('/api/v1/matches/saved');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
