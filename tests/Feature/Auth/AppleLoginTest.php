<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppleLoginTest extends TestCase
{
    use RefreshDatabase;

    private const KID = 'test-apple-key';
    private const AUD = 'com.tab3.app';

    private string $privateKeyPem;
    private string $opensslConfig;

    protected function setUp(): void
    {
        parent::setUp();

        // openssl_pkey_new() needs an openssl.cnf, which isn't guaranteed to
        // exist on dev/CI machines. Write a minimal one to a temp file so key
        // generation is portable.
        $this->opensslConfig = sys_get_temp_dir() . '/apple_test_openssl.cnf';
        file_put_contents($this->opensslConfig, "[req]\ndistinguished_name = dn\n[dn]\n");

        // Generate a throwaway RSA keypair. The private half signs the identity
        // token; the public half is served as Apple's JWKS via Http::fake so the
        // signature verifies without ever contacting appleid.apple.com.
        [$this->privateKeyPem, $details] = $this->generateKeypair();

        $n = $this->base64url($details['rsa']['n']);
        $e = $this->base64url($details['rsa']['e']);

        Http::fake([
            'appleid.apple.com/auth/keys' => Http::response([
                'keys' => [[
                    'kty' => 'RSA',
                    'kid' => self::KID,
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => $n,
                    'e' => $e,
                ]],
            ], 200),
        ]);

        // The app whose bundle ID minted these tokens is on the allowlist.
        config(['services.apple.client_ids' => [self::AUD]]);
    }

    private function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /** @return array{0: string, 1: array} [private key PEM, key details] */
    private function generateKeypair(): array
    {
        $opts = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $this->opensslConfig,
        ];
        $res = openssl_pkey_new($opts);
        openssl_pkey_export($res, $pem, null, ['config' => $this->opensslConfig]);

        return [$pem, openssl_pkey_get_details($res)];
    }

    /** Build a signed Apple identity token, overriding any claim for edge cases. */
    private function appleToken(array $overrides = []): string
    {
        $payload = array_merge([
            'iss' => 'https://appleid.apple.com',
            'aud' => self::AUD,
            'sub' => 'apple-user-001',
            'email' => 'apple.user@example.com',
            'iat' => time(),
            'exp' => time() + 3600,
        ], $overrides);

        return JWT::encode($payload, $this->privateKeyPem, 'RS256', self::KID);
    }

    /** @test */
    public function it_registers_a_new_fan_on_first_apple_login()
    {
        $response = $this->postJson('/api/v1/auth/login/apple', [
            'apple_token' => $this->appleToken(),
            'name' => 'Apple Fan',
            'role' => 'fan',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'apple_id' => 'apple-user-001',
            'email' => 'apple.user@example.com',
            'name' => 'Apple Fan',
            'role' => 'fan',
        ]);

        $user = User::where('apple_id', 'apple-user-001')->first();
        $this->assertDatabaseHas('fan_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseHas('loyalty_cards', ['user_id' => $user->id]);
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function it_authenticates_an_existing_user_by_apple_id_without_duplicating()
    {
        User::factory()->create([
            'apple_id' => 'apple-user-001',
            'email' => 'apple.user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login/apple', [
            'apple_token' => $this->appleToken(),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(1, User::where('apple_id', 'apple-user-001')->count());
    }

    /** @test */
    public function it_links_apple_id_to_an_existing_email_account()
    {
        $existing = User::factory()->create([
            'apple_id' => null,
            'email' => 'apple.user@example.com',
        ]);

        $this->postJson('/api/v1/auth/login/apple', [
            'apple_token' => $this->appleToken(),
        ])->assertStatus(200);

        $this->assertSame('apple-user-001', $existing->fresh()->apple_id);
    }

    /** @test */
    public function it_requires_a_role_for_new_users()
    {
        $response = $this->postJson('/api/v1/auth/login/apple', [
            'apple_token' => $this->appleToken(),
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('users', ['apple_id' => 'apple-user-001']);
    }

    /** @test */
    public function it_rejects_a_token_whose_audience_is_not_allowlisted()
    {
        $response = $this->postJson('/api/v1/auth/login/apple', [
            'apple_token' => $this->appleToken(['aud' => 'com.someone.else']),
            'role' => 'fan',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('users', ['apple_id' => 'apple-user-001']);
    }

    /** @test */
    public function it_rejects_a_token_signed_by_a_foreign_key()
    {
        // A token signed by a different keypair must fail signature verification
        // against Apple's published (faked) public key.
        [$foreignPem] = $this->generateKeypair();

        $forged = JWT::encode([
            'iss' => 'https://appleid.apple.com',
            'aud' => self::AUD,
            'sub' => 'apple-user-001',
            'email' => 'apple.user@example.com',
            'iat' => time(),
            'exp' => time() + 3600,
        ], $foreignPem, 'RS256', self::KID);

        $response = $this->postJson('/api/v1/auth/login/apple', [
            'apple_token' => $forged,
            'role' => 'fan',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('users', ['apple_id' => 'apple-user-001']);
    }
}
