<?php

namespace App\Services;

use App\Models\User;
use App\Models\FanProfile;
use App\Models\LoyaltyCard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use App\Notifications\PasswordResetOtp;
use App\Notifications\EmailVerificationOtp;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class AuthService
{
    /**
     * Register a new user with the specified role
     *
     * @param array $data
     * @param string $role
     * @return array
     */
    public function register(array $data, string $role = 'fan'): array
    {
        $result = DB::transaction(function () use ($data, $role) {
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $role,
                'is_active' => true,
            ]);

            // If role is fan, create fan profile and loyalty card
            if ($role === 'fan') {
                // Create fan profile
                $fanProfile = FanProfile::create([
                    'user_id' => $user->id,
                    'member_since' => now(),
                    'total_bookings' => 0,
                    'is_verified' => false,
                ]);

                // Create loyalty card
                $loyaltyCard = LoyaltyCard::create([
                    'user_id' => $user->id,
                    'card_number' => $this->generateLoyaltyCardNumber(),
                    'points' => 0,
                    'tier' => 'bronze',
                    'issued_date' => now(),
                ]);
            }

            // If role is cafe_owner, assign manage-cafe-profile permission
            // Cafe owner permissions are handled via role-based can() override
            // No need to assign individual permissions

            // Create Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load relationships
            $user->load(['fanProfile', 'loyaltyCard']);

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        // Send OTP verification email outside the transaction so a mail
        // failure doesn't roll back the user creation
        $this->sendEmailVerificationOtp($result['user']);

        return $result;
    }

    /**
     * Verify-first registration — STEP 1: send an OTP to the email.
     *
     * No User row is created here. This proves the person actually controls the
     * inbox before any account exists, which kills "unverified email squatting":
     * you can't claim an email you can't receive mail for.
     *
     * @param string $email
     * @return array
     * @throws ValidationException
     */
    public function requestRegistrationOtp(string $email): array
    {
        // Only block if a fully-verified account already owns this email.
        $existing = User::where('email', $email)->first();
        if ($existing && $existing->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered. Please log in instead.'],
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("reg_otp:{$email}", $otp, now()->addMinutes(10));

        // On-demand notification: send to a raw email address (no User model yet).
        Notification::route('mail', $email)
            ->notify(new EmailVerificationOtp($otp, 'there', $email));

        return [
            'message' => 'A verification code has been sent to your email.',
        ];
    }

    /**
     * Verify-first registration — STEP 2: check the OTP.
     *
     * Still no User row. We just record a short-lived "this email is proven" flag
     * so STEP 3 is allowed. The flag expires so an abandoned signup can't be
     * completed by someone else much later.
     *
     * @param string $email
     * @param string $otp
     * @return array
     * @throws ValidationException
     */
    public function verifyRegistrationOtp(string $email, string $otp): array
    {
        $storedOtp = Cache::get("reg_otp:{$email}");

        if (!$storedOtp || $storedOtp !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired verification code.'],
            ]);
        }

        Cache::forget("reg_otp:{$email}");
        Cache::put("reg_verified:{$email}", true, now()->addMinutes(30));

        return [
            'message' => 'Email verified. Please complete your registration.',
        ];
    }

    /**
     * Verify-first registration — STEP 3: create the account.
     *
     * Only reachable if STEP 2's proof flag exists. The account is born already
     * verified (email_verified_at set), so there is no follow-up verification step.
     * If a legacy unverified row exists for this email, it is claimed in place —
     * which also cleans up old squatted accounts.
     *
     * @param array $data
     * @param string $role
     * @return array
     * @throws ValidationException
     */
    public function completeRegistration(array $data, string $role = 'fan'): array
    {
        $email = $data['email'];

        if (!Cache::get("reg_verified:{$email}")) {
            throw ValidationException::withMessages([
                'email' => ['Please verify your email before completing registration.'],
            ]);
        }

        $result = DB::transaction(function () use ($data, $email, $role) {
            $existing = User::where('email', $email)->first();

            if ($existing && $existing->email_verified_at !== null) {
                // A real account appeared in the meantime — don't overwrite it.
                throw ValidationException::withMessages([
                    'email' => ['This email is already registered. Please log in instead.'],
                ]);
            }

            if ($existing) {
                // Claim a legacy unverified row in place.
                $user = $existing;
                $user->name = $data['name'];
                $user->phone = $data['phone'] ?? $user->phone;
                $user->password = Hash::make($data['password']);
                $user->role = $role;
                $user->is_active = true;
                $user->email_verified_at = now();
                $user->save();
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password']),
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }

            if ($role === 'fan') {
                FanProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    ['member_since' => now(), 'total_bookings' => 0, 'is_verified' => false]
                );

                LoyaltyCard::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'card_number' => $this->generateLoyaltyCardNumber(),
                        'points' => 0,
                        'tier' => 'bronze',
                        'issued_date' => now(),
                    ]
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(['fanProfile', 'loyaltyCard']);

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        // Proof consumed — clear it so the flag can't be reused.
        Cache::forget("reg_verified:{$email}");

        return $result;
    }

    /**
     * Login user with email or phone
     *
     * @param string $emailOrPhone
     * @param string $password
     * @return array
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function login(string $emailOrPhone, string $password): array
    {
        // Determine if input is email or phone
        $field = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Find user
        $user = User::where($field, $emailOrPhone)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        // Check if user is active
        if (!$user->is_active) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Your account has been deactivated.');
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load relationships
        $user->load(['fanProfile', 'loyaltyCard']);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Login or register user with Google
     *
     * @param string $googleToken
     * @return array
     * @throws ValidationException
     */
    public function loginWithGoogle(string $googleToken): array
    {
        try {
            // Initialize Google Client
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            
            // Verify the ID token
            $payload = $client->verifyIdToken($googleToken);
            
            if (!$payload) {
                throw new \Exception('Invalid Google token');
            }
            
            // Extract user data from verified token
            $googleUser = [
                'email' => $payload['email'],
                'name' => $payload['name'] ?? $payload['email'],
                'sub' => $payload['sub'],
                'email_verified' => $payload['email_verified'] ?? false,
                'picture' => $payload['picture'] ?? null,
            ];

            // Check if user exists by google_id or email
            $user = User::where('google_id', $googleUser['sub'])
                ->orWhere('email', $googleUser['email'])
                ->first();

            if (!$user) {
                // Create new user
                $user = DB::transaction(function () use ($googleUser) {
                    $user = User::create([
                        'name' => $googleUser['name'],
                        'email' => $googleUser['email'],
                        'google_id' => $googleUser['sub'],
                        'avatar' => $googleUser['picture'] ? ['url' => $googleUser['picture'], 'source' => 'google'] : null,
                        'password' => Hash::make(uniqid()), // Random password for OAuth users
                        'role' => 'fan',
                        'is_active' => true,
                        'email_verified_at' => $googleUser['email_verified'] ? now() : null,
                    ]);

                    // Create fan profile
                    FanProfile::create([
                        'user_id' => $user->id,
                        'member_since' => now(),
                        'total_bookings' => 0,
                        'is_verified' => false,
                    ]);

                    // Create loyalty card
                    LoyaltyCard::create([
                        'user_id' => $user->id,
                        'card_number' => $this->generateLoyaltyCardNumber(),
                        'points' => 0,
                        'tier' => 'bronze',
                        'issued_date' => now(),
                    ]);

                    return $user;
                });
            }

            // Update google_id, avatar, and email_verified_at if user exists but doesn't have them yet
            $updates = [];
            if (!$user->google_id) {
                $updates['google_id'] = $googleUser['sub'];
            }
            if (!$user->avatar && $googleUser['picture']) {
                $updates['avatar'] = ['url' => $googleUser['picture'], 'source' => 'google'];
            }
            if (!$user->email_verified_at && $googleUser['email_verified']) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->update($updates);
                $user->refresh();
            }

            // Check if user is active
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'google_token' => ['Your account has been deactivated.'],
                ]);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load relationships
            $user->load(['fanProfile', 'loyaltyCard']);

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            \Log::error('Google OAuth error: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'google_token' => ['Failed to authenticate with Google: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Login or register user with Apple
     *
     * @param string $appleToken
     * @param string|null $name
     * @return array
     * @throws ValidationException
     */
    public function loginWithApple(string $appleToken, ?string $name = null): array
    {
        try {
            // Fetch Apple's public keys
            $keysResponse = Http::get('https://appleid.apple.com/auth/keys');
            
            if ($keysResponse->failed()) {
                throw new \Exception('Failed to fetch Apple public keys');
            }
            
            $keys = $keysResponse->json()['keys'];
            
            // Parse the JWT header to get the key ID
            $tokenParts = explode('.', $appleToken);
            if (count($tokenParts) !== 3) {
                throw new \Exception('Invalid Apple token format');
            }
            
            $header = json_decode(base64_decode($tokenParts[0]), true);
            
            if (!isset($header['kid'])) {
                throw new \Exception('Missing key ID in token header');
            }
            
            // Find the matching public key
            $publicKey = null;
            foreach ($keys as $key) {
                if ($key['kid'] === $header['kid']) {
                    $publicKey = JWK::parseKeySet(['keys' => [$key]]);
                    break;
                }
            }
            
            if (!$publicKey) {
                throw new \Exception('Unable to find matching public key');
            }
            
            // Verify and decode the JWT
            $payload = JWT::decode($appleToken, $publicKey);
            
            // Validate the token
            if ($payload->iss !== 'https://appleid.apple.com') {
                throw new \Exception('Invalid issuer');
            }
            
            if ($payload->aud !== config('services.apple.client_id')) {
                throw new \Exception('Invalid audience');
            }
            
            if ($payload->exp < time()) {
                throw new \Exception('Token expired');
            }
            
            // Extract user data
            $appleUserId = $payload->sub;
            $email = $payload->email ?? null;
            
            // Check if user exists by apple_id or email
            $user = User::where('apple_id', $appleUserId)->first();
            
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                // Create new user
                $user = DB::transaction(function () use ($email, $name, $appleUserId) {
                    $user = User::create([
                        'name' => $name ?? ($email ? explode('@', $email)[0] : 'Apple User'),
                        'email' => $email ?? $appleUserId . '@privaterelay.appleid.com',
                        'apple_id' => $appleUserId,
                        'password' => Hash::make(uniqid()), // Random password for OAuth users
                        'role' => 'fan',
                        'is_active' => true,
                        'email_verified_at' => now(), // Auto-verify OAuth users
                    ]);

                    // Create fan profile
                    FanProfile::create([
                        'user_id' => $user->id,
                        'member_since' => now(),
                        'total_bookings' => 0,
                        'is_verified' => false,
                    ]);

                    // Create loyalty card
                    LoyaltyCard::create([
                        'user_id' => $user->id,
                        'card_number' => $this->generateLoyaltyCardNumber(),
                        'points' => 0,
                        'tier' => 'bronze',
                        'issued_date' => now(),
                    ]);

                    return $user;
                });
            }
            
            // Update apple_id if user exists but doesn't have it
            if (!$user->apple_id) {
                $user->update(['apple_id' => $appleUserId]);
            }

            // Check if user is active
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'apple_token' => ['Your account has been deactivated.'],
                ]);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load relationships
            $user->load(['fanProfile', 'loyaltyCard']);

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            \Log::error('Apple Sign In error: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'apple_token' => ['Failed to authenticate with Apple: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Logout user by revoking current token
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        // Revoke current token
        $user->currentAccessToken()->delete();

        return true;
    }

    /**
     * Refresh user token
     *
     * @param User $user
     * @return array
     */
    public function refreshToken(User $user): array
    {
        // Revoke current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
        ];
    }

    /**
     * Get authenticated user profile with relationships
     *
     * @param User $user
     * @return array
     */
    public function getProfile(User $user): array
    {
        // Use raw queries to avoid any circular references
        $userData = \DB::table('users')->where('id', $user->id)->first();
        $fanProfile = \DB::table('fan_profiles')->where('user_id', $user->id)->first();
        $loyaltyCard = \DB::table('loyalty_cards')->where('user_id', $user->id)->first();
        
        return [
            'id' => $userData->id,
            'name' => $userData->name,
            'email' => $userData->email,
            'phone' => $userData->phone,
            'role' => $userData->role,
            'created_at' => $userData->created_at,
            'fan_profile' => $fanProfile ? [
                'favorite_team_id' => $fanProfile->favorite_team_id,
                'preferred_team_id' => $fanProfile->preferred_team_id,
                'member_since' => $fanProfile->member_since,
            ] : null,
            'loyalty_card' => $loyaltyCard ? [
                'card_number' => $loyaltyCard->card_number,
                'points' => $loyaltyCard->points,
                'tier' => $loyaltyCard->tier,
                'issued_date' => $loyaltyCard->issued_date,
            ] : null,
        ];
    }

    /**
     * Generate unique loyalty card number
     *
     * @return string
     */
    private function generateLoyaltyCardNumber(): string
    {
        do {
            $cardNumber = 'MD' . date('Y') . str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (LoyaltyCard::where('card_number', $cardNumber)->exists());

        return $cardNumber;
    }

    /**
     * Send password reset OTP to user's email
     *
     * @param string $email
     * @return array
     */
    public function sendPasswordResetOtp(string $email): array
    {
        // Always return success for security (don't reveal if email exists)
        // But only send OTP if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Cache::put("otp:{$user->email}", $otp, now()->addMinutes(10));
            $user->notify(new PasswordResetOtp($otp, $user->name, $user->email));
        }

        return [
            'message' => 'If the email exists, a password reset OTP has been sent.',
        ];
    }

    /**
     * Reset password using OTP
     *
     * @param string $email
     * @param string $otp
     * @param string $password
     * @return array
     * @throws ValidationException
     */
    public function resetPassword(string $email, string $otp, string $password): array
    {
        // Check if user exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.'],
            ]);
        }

        $storedOtp = Cache::get("otp:{$user->email}");

        if (!$storedOtp || $storedOtp !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $user->password = Hash::make($password);
        $user->save();

        Cache::forget("otp:{$user->email}");

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return [
            'message' => 'Password has been reset successfully.',
        ];
    }

    /**
     * Send email verification OTP to authenticated user
     *
     * @param User $user
     * @return array
     * @throws ValidationException
     */
    public function sendEmailVerificationOtp(User $user): array
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("otp:{$user->email}", $otp, now()->addMinutes(10));
        $user->notify(new EmailVerificationOtp($otp, $user->name, $user->email));

        return [
            'message' => 'Verification OTP has been sent to your email.',
        ];
    }

    /**
     * Verify email using OTP
     *
     * @param User $user
     * @param string $otp
     * @return array
     * @throws ValidationException
     */
    public function verifyEmail(User $user, string $otp): array
    {
        // Check if email is already verified
        if ($user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Email is already verified.'],
            ]);
        }

        $storedOtp = Cache::get("otp:{$user->email}");

        if (!$storedOtp || $storedOtp !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        Cache::forget("otp:{$user->email}");

        return [
            'message' => 'Email has been verified successfully.',
            'user' => $user,
        ];
    }
}
