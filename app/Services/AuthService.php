<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeNotification;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthService
{
    private const RESET_OTP_TTL_MINUTES = 10;
    private const RESET_OTP_MAX_ATTEMPTS = 5;

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected SmsService $smsService
    ) {}

    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'customer';

        $user = $this->userRepository->create($data);
        if (! $user instanceof User) {
            throw new \RuntimeException('Failed to create user.');
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Send welcome notification
        $user->notify(new WelcomeNotification);

        // Send email verification
        $this->sendVerificationEmail($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): ?array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    public function sendPasswordResetLink(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            return false;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Generate OTP for optional SMS-based reset verification.
        $otp = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        Cache::put($this->otpCacheKey($email), Hash::make($otp), now()->addMinutes(self::RESET_OTP_TTL_MINUTES));

        if (! empty($user->phone)) {
            $smsResult = $this->smsService->sendOtp($user->phone, $otp);

            if (! $smsResult['success']) {
                Log::warning('Failed to send password reset OTP SMS.', [
                    'email' => $email,
                    'provider_code' => $smsResult['code'] ?? null,
                ]);
            }
        }

        $user->notify(new ResetPasswordNotification($token));

        return true;
    }

    public function resetPassword(array $data): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $record) {
            return false;
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            Cache::forget($this->otpCacheKey($data['email']));

            return false;
        }

        $otp = trim((string) ($data['otp'] ?? ''));

        if ($otp !== '') {
            if (! $this->verifyOtp($data['email'], $otp)) {
                return false;
            }
        } else {
            if (! isset($data['token']) || ! Hash::check($data['token'], $record->token)) {
                return false;
            }
        }

        $user = $this->userRepository->findByEmail($data['email']);

        if (! $user) {
            return false;
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        Cache::forget($this->otpCacheKey($data['email']));

        // Revoke all existing tokens
        $user->tokens()->delete();

        return true;
    }

    private function otpCacheKey(string $email): string
    {
        return 'password_reset_otp:'.strtolower(trim($email));
    }

    private function otpAttemptsCacheKey(string $email): string
    {
        return $this->otpCacheKey($email).':attempts';
    }

    private function verifyOtp(string $email, string $otp): bool
    {
        $attemptsKey = $this->otpAttemptsCacheKey($email);
        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= self::RESET_OTP_MAX_ATTEMPTS) {
            return false;
        }

        $otpHash = Cache::get($this->otpCacheKey($email));

        if (! is_string($otpHash) || $otpHash === '') {
            return false;
        }

        if (Hash::check($otp, $otpHash)) {
            Cache::forget($attemptsKey);
            return true;
        }

        Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(self::RESET_OTP_TTL_MINUTES));
        return false;
    }

    public function sendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $user->notify(new VerifyEmailNotification($verificationUrl));
    }

    public function verifyEmail(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->markEmailAsVerified();

        return true;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all other tokens except the current one
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
    }
}
