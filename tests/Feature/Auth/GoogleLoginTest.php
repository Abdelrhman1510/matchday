<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private const KID = 'test-google-key';
    private const AUD = 'test-google-client.apps.googleusercontent.com';

    private string $privateKeyPem;
    private string $opensslConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opensslConfig = sys_get_temp_dir() . '/google_test_openssl.cnf';
        file_put_contents($this->opensslConfig, "[req]\ndistinguished_name = dn\n[dn]\n");

        $opts = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $this->opensslConfig,
        ];
        $res = openssl_pkey_new($opts);
        openssl_pkey_export($res, $pem, null, ['config' => $this->opensslConfig]);
        $this->privateKeyPem = $pem;
        $details = openssl_pkey_get_details($res);

        Http::fake([
            'www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [[
                    'kty' => 'RSA',
                    'kid' => self::KID,
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => $this->base64url($details['rsa']['n']),
                    'e' => $this->base64url($details['rsa']['e']),
                ]],
            ], 200),
        ]);

        config(['services.google.client_ids' => [self::AUD]]);
    }

    private function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function googleToken(array $overrides = []): string
    {
        $payload = array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => self::AUD,
            'sub' => 'google-user-001',
            'email' => 'google.user@example.com',
            'email_verified' => true,
            'name' => 'Google User',
            'iat' => time(),
            'exp' => time() + 3600,
        ], $overrides);

        return JWT::encode($payload, $this->privateKeyPem, 'RS256', self::KID);
    }

    /** @test */
    public function it_registers_a_new_fan_on_first_google_login()
    {
        $response = $this->postJson('/api/v1/auth/login/google', [
            'google_token' => $this->googleToken(),
            'role' => 'fan',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'google_id' => 'google-user-001',
            'email' => 'google.user@example.com',
        ]);
    }

    /** @test */
    public function it_authenticates_an_existing_google_user_without_duplicating()
    {
        User::factory()->create([
            'google_id' => 'google-user-001',
            'email' => 'google.user@example.com',
        ]);

        $this->postJson('/api/v1/auth/login/google', [
            'google_token' => $this->googleToken(),
        ])->assertStatus(200);

        $this->assertSame(1, User::where('google_id', 'google-user-001')->count());
    }

    /** @test */
    public function it_rejects_a_google_token_whose_audience_is_not_allowlisted()
    {
        $response = $this->postJson('/api/v1/auth/login/google', [
            'google_token' => $this->googleToken(['aud' => 'someone-else.apps.googleusercontent.com']),
            'role' => 'fan',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('users', ['google_id' => 'google-user-001']);
    }

    /** @test */
    public function it_rejects_a_google_token_when_no_client_ids_are_configured()
    {
        // This is the BUG-093 production scenario: if GOOGLE_CLIENT_IDS is unset
        // on the server, the allowlist is empty and every token is (correctly)
        // rejected — which reads as "login is non-functional".
        config(['services.google.client_ids' => []]);

        $this->postJson('/api/v1/auth/login/google', [
            'google_token' => $this->googleToken(),
            'role' => 'fan',
        ])->assertStatus(422);
    }
}
