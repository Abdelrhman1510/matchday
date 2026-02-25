<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\AppleLoginRequest;
use App\Http\Requests\CafeOwnerRegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserProfileResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new fan user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated(), 'fan');

            return $this->successResponse(
                [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ],
                'Registration successful. Please check your email to verify your account.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Register a new cafe owner
     *
     * @param CafeOwnerRegisterRequest $request
     * @return JsonResponse
     */
    public function registerCafeOwner(CafeOwnerRegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated(), 'cafe_owner');

            return $this->successResponse(
                [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ],
                'Cafe owner registration successful. Please check your email to verify your account.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Login user with email or phone
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->input('email_or_phone'),
                $request->input('password')
            );

            return $this->successResponse(
                [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ],
                'Login successful'
            );
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                401
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getStatusCode()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Login with Google
     *
     * @param GoogleLoginRequest $request
     * @return JsonResponse
     */
    public function loginWithGoogle(GoogleLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginWithGoogle($request->input('google_token'));

            return $this->successResponse(
                [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ],
                'Google login successful'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Login with Apple
     *
     * @param AppleLoginRequest $request
     * @return JsonResponse
     */
    public function loginWithApple(AppleLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginWithApple(
                $request->input('apple_token'),
                $request->input('name')
            );

            return $this->successResponse(
                [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ],
                'Apple login successful'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            auth('sanctum')->forgetUser();

            return $this->successResponse(
                null,
                'Logout successful'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Refresh user token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Delete current token
            $user->currentAccessToken()->delete();
            
            // Create new token
            $newToken = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(
                ['token' => $newToken],
                'Token refreshed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $fanProfile = DB::table('fan_profiles')->where('user_id', $user->id)->first();
            $loyaltyCard = DB::table('loyalty_cards')->where('user_id', $user->id)->first();
            
            $hasTeamSelected = $fanProfile && $fanProfile->favorite_team_id !== null;

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'needs_team_selection' => !$hasTeamSelected,
                'onboarding_complete' => $hasTeamSelected,
                'fan_profile' => $fanProfile ? [
                    'favorite_team_id' => $fanProfile->favorite_team_id,
                    'member_since' => $fanProfile->member_since,
                ] : null,
                'loyalty_card' => $loyaltyCard ? [
                    'card_number' => $loyaltyCard->card_number,
                    'points' => $loyaltyCard->points,
                    'tier' => $loyaltyCard->tier,
                    'issued_date' => $loyaltyCard->issued_date,
                ] : null,
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => ['user' => $userData],
                'meta' => (object)[],
            ]);
        } catch (\Exception $e) {
            Log::error('Profile error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'data' => (object)[],
                'meta' => (object)[],
            ], 500);
        }
    }

    /**
     * Send password reset OTP to user's email
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->sendPasswordResetOtp($request->email);

            return $this->successResponse(
                [],
                $result['message'],
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Reset password using OTP
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->resetPassword(
                $request->email,
                $request->otp,
                $request->password
            );

            return $this->successResponse(
                [],
                $result['message'],
                200
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Verify email using OTP (requires authentication)
     *
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $result = $this->authService->verifyEmail($user, $request->otp);

            return $this->successResponse(
                ['user' => $result['user']],
                $result['message'],
                200
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Resend email verification OTP (requires authentication)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendVerificationOtp(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $result = $this->authService->sendEmailVerificationOtp($user);

            return $this->successResponse(
                [],
                $result['message'],
                200
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Accept staff invitation via signed URL
     * Public endpoint - no auth required, token validates
     *
     * @param string $token
     * @return JsonResponse
     */
    public function acceptStaffInvite(string $token): JsonResponse
    {
        try {
            $staffService = app(\App\Services\StaffService::class);
            $staffMember = $staffService->acceptInvitation($token);

            return $this->successResponse(
                [
                    'staff_member' => new \App\Http\Resources\StaffResource($staffService->getStaffDetail($staffMember)),
                ],
                'Staff invitation accepted successfully. You can now log in.',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
