<?php

namespace App\Services;

use App\Models\User;
use App\Models\TransactionPassword;
use Illuminate\Support\Facades\Hash;
use App\Events\TransactionPasswordCreated;
use App\Events\TransactionPasswordChanged;
use App\Events\TransactionPasswordFailed;
use App\Events\TransactionPasswordLocked;
use App\Events\TransactionPasswordVerified;
use App\Events\SensitiveActionConfirmed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionPasswordService
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;
    public const SESSION_TTL_MINUTES = 15;
    private const SESSION_KEY = 'transaction_verified_at';

    /**
     * Check if user has a transaction password set up.
     */
    public function hasPassword(User $user): bool
    {
        return $user->transactionPassword()->exists();
    }

    /**
     * Create initial transaction password.
     */
    public function createPassword(User $user, string $password): bool
    {
        if ($this->hasPassword($user)) {
            return false;
        }

        $user->transactionPassword()->create([
            'password_hash' => Hash::make($password),
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_changed_at' => now(),
        ]);

        event(new TransactionPasswordCreated($user));
        $this->logAudit('created', $user);

        return true;
    }

    /**
     * Change an existing transaction password.
     */
    public function changePassword(User $user, string $newPassword): bool
    {
        $tp = $user->transactionPassword;
        if (!$tp) {
            return false;
        }

        $tp->update([
            'password_hash' => Hash::make($newPassword),
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_changed_at' => now(),
        ]);

        $this->forgetRecentVerification();

        event(new TransactionPasswordChanged($user));
        $this->logAudit('changed', $user);

        return true;
    }

    /**
     * Check if user is currently locked out.
     */
    public function isLockedOut(User $user): bool
    {
        $tp = $user->transactionPassword;
        if (!$tp || !$tp->locked_until) {
            return false;
        }

        if (Carbon::now()->isBefore($tp->locked_until)) {
            return true;
        }

        // Lock expired, reset attempts
        $tp->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        return false;
    }

    /**
     * Validate the given transaction password.
     */
    public function validate(User $user, string $password): bool
    {
        if ($this->isLockedOut($user)) {
            $this->logAudit('locked_attempt', $user);
            return false;
        }

        $tp = $user->transactionPassword;
        if (!$tp) {
            return false;
        }

        if (Hash::check($password, $tp->password_hash)) {
            // Success, reset attempts
            $tp->update(['failed_attempts' => 0]);
            $this->markRecentlyVerified();
            event(new TransactionPasswordVerified($user));
            event(new SensitiveActionConfirmed($user));
            $this->logAudit('verified', $user);
            return true;
        }

        // Failed attempt
        $attempts = $tp->failed_attempts + 1;
        
        if ($attempts >= self::MAX_ATTEMPTS) {
            $tp->update([
                'failed_attempts' => $attempts,
                'locked_until' => Carbon::now()->addMinutes(self::LOCKOUT_MINUTES),
            ]);
            event(new TransactionPasswordLocked($user));
            $this->logAudit('locked', $user);
        } else {
            $tp->update(['failed_attempts' => $attempts]);
            event(new TransactionPasswordFailed($user));
            $this->logAudit('failed', $user);
        }

        return false;
    }

    public function isRecentlyVerified(): bool
    {
        if (! request()->hasSession()) {
            return false;
        }

        $verifiedAt = request()->session()->get(self::SESSION_KEY);

        if (! $verifiedAt) {
            return false;
        }

        return Carbon::parse($verifiedAt)->addMinutes(self::SESSION_TTL_MINUTES)->isFuture();
    }

    public function forgetRecentVerification(): void
    {
        if (request()->hasSession()) {
            request()->session()->forget(self::SESSION_KEY);
        }
    }

    public function verifyRequest(Request $request, ?User $user = null): bool
    {
        $user ??= $request->user();

        if (! $user || ! $this->hasPassword($user)) {
            return false;
        }

        if ($this->isRecentlyVerified()) {
            return true;
        }

        $password = (string) $request->input('transaction_password', $request->header('X-Transaction-Pin', ''));

        if (! preg_match('/^\d{4}$/', $password)) {
            return false;
        }

        return $this->validate($user, $password);
    }

    public function remainingAttempts(User $user): int
    {
        $tp = $user->transactionPassword;

        if (! $tp) {
            return 0;
        }

        return max(0, self::MAX_ATTEMPTS - (int) $tp->failed_attempts);
    }

    private function markRecentlyVerified(): void
    {
        if (request()->hasSession()) {
            request()->session()->put(self::SESSION_KEY, now()->toIso8601String());
        }
    }

    private function logAudit(string $event, User $user): void
    {
        Log::info('transaction_password.'.$event, [
            'user_id' => $user->id,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
