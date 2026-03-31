<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeNotification;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'customer';

        $user = $this->userRepository->create($data);
        $token = $user->createToken('auth-token')->plainTextToken;

        // Send welcome notification
        $user->notify(new WelcomeNotification());

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

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
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

        if (!$user) {
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

        $user->notify(new ResetPasswordNotification($token));

        return true;
    }

    public function resetPassword(array $data): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (!$record) {
            return false;
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            return false;
        }

        if (!Hash::check($data['token'], $record->token)) {
            return false;
        }

        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user) {
            return false;
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        // Revoke all existing tokens
        $user->tokens()->delete();

        return true;
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
