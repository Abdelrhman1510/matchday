<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bug82Test extends TestCase
{
    use RefreshDatabase;

    public function test_bug_82_deleted_cafe_owner()
    {
        $this->withoutExceptionHandling();

        // 1. Register a cafe owner
        $response = $this->postJson('/api/v1/auth/register/cafe-owner', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '123456789'
        ]);
        
        $this->assertEquals(201, $response->status());
        
        // Mock verification
        $user = User::where('email', 'owner@example.com')->first();
        $user->email_verified_at = now();
        $user->save();
        $token = $user->createToken('test')->plainTextToken;
        
        // 2. Add cafe details
        $cafeResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v1/admin/cafes', [
                'name' => 'Old Cafe Name',
                'phone' => '987654321',
                'city' => 'Test City'
            ]);
            
        $this->assertEquals(201, $cafeResponse->status());
        $cafeId = $cafeResponse->json('data.id');
        
        $this->assertDatabaseHas('cafes', ['name' => 'Old Cafe Name', 'owner_id' => $user->id]);

        // 3. Delete account
        $deleteResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->deleteJson('/api/v1/profile');
            
        $this->assertEquals(200, $deleteResponse->status());
        
        // Ensure cafe is deleted
        $this->assertDatabaseMissing('cafes', ['id' => $cafeId]);

        \Illuminate\Support\Facades\Cache::forget('otp_cooldown:verify:owner@example.com');
        \Illuminate\Support\Facades\Cache::forget('otp_count:verify:owner@example.com');

        // 4. Register new account with same email
        $response2 = $this->postJson('/api/v1/auth/register/cafe-owner', [
            'name' => 'New Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '111111111'
        ]);
        
        if ($response2->status() !== 201) {
            dump($response2->json());
        }
        $this->assertEquals(201, $response2->status());
        
        $newUser = User::where('email', 'owner@example.com')->first();
        $newUser->email_verified_at = now();
        $newUser->save();
        $newToken = $newUser->createToken('test2')->plainTextToken;
        
        // What does the onboarding endpoint return?
        $onboarding = $this->withHeaders(['Authorization' => 'Bearer ' . $newToken])
            ->getJson('/api/v1/admin/cafes');
            
        // Log the onboarding response
        \Log::info('Onboarding response', $onboarding->json());
        
        // We expect it to be empty/not found/etc since there is no cafe for the new user yet
        $this->assertEquals(200, $onboarding->status());
        $this->assertEmpty($onboarding->json('data'));
    }
}
