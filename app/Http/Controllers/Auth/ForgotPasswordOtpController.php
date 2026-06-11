<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordOtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordOtpController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower($validated['email']);
        $userExists = User::query()->whereRaw('lower(email) = ?', [$email])->exists();

        if ($userExists) {
            $otp = (string) random_int(100000, 999999);

            PasswordOtp::query()->updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                    'attempts' => 0,
                    'verified_at' => null,
                ],
            );

            Mail::to($email)->send(new PasswordOtpMail($otp, self::OTP_TTL_MINUTES));
        }

        return response()->json([
            'message' => 'Nếu email tồn tại trong hệ thống, OTP đã được gửi.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = Str::lower($validated['email']);
        $otp = (string) $validated['otp'];
        $passwordOtp = PasswordOtp::query()->where('email', $email)->first();

        if (!$passwordOtp || $passwordOtp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => ['OTP không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        if ($passwordOtp->attempts >= self::MAX_ATTEMPTS) {
            return response()->json([
                'message' => 'Bạn đã nhập sai quá số lần cho phép.',
            ], 429);
        }

        if (!Hash::check($otp, $passwordOtp->otp_hash)) {
            $passwordOtp->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => ['OTP không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        $passwordOtp->forceFill([
            'verified_at' => now(),
        ])->save();

        $resetToken = Str::random(64);

        Cache::put(
            $this->resetTokenCacheKey($email),
            hash('sha256', $resetToken),
            now()->addMinutes(self::OTP_TTL_MINUTES),
        );

        return response()->json([
            'reset_token' => $resetToken,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = Str::lower($validated['email']);
        $cacheKey = $this->resetTokenCacheKey($email);
        $cachedTokenHash = Cache::get($cacheKey);
        $providedTokenHash = hash('sha256', $validated['reset_token']);

        if (
            !is_string($cachedTokenHash)
            || !hash_equals($cachedTokenHash, $providedTokenHash)
        ) {
            throw ValidationException::withMessages([
                'reset_token' => ['Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'reset_token' => ['Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        Cache::forget($cacheKey);
        PasswordOtp::query()->where('email', $email)->delete();

        return response()->json([
            'message' => 'Đặt lại mật khẩu thành công.',
        ]);
    }

    private function resetTokenCacheKey(string $email): string
    {
        return 'pwd_reset_token_' . $email;
    }
}
