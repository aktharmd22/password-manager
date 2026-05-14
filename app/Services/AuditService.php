<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Record a single audit event. Designed to never throw — audit logging
     * must not break the user's primary action. Failures are swallowed and
     * forwarded to the laravel log channel (without sensitive data).
     */
    public function log(
        string $action,
        ?User $user = null,
        ?Credential $credential = null,
        array $metadata = [],
        ?Request $request = null,
    ): ?AuditLog {
        try {
            $request ??= request();
            $user ??= $request?->user();

            // Strip anything that smells like a secret before persisting metadata.
            $metadata = $this->sanitizeMetadata($metadata);

            return AuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'credential_id' => $credential?->id,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
                'metadata' => $metadata ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Remove keys that look like they hold sensitive values before saving.
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $forbidden = ['password', 'password_encrypted', 'secret', 'token', 'plaintext', 'notes', 'custom_fields'];

        return collect($metadata)
            ->reject(fn ($_, $key) => in_array(strtolower((string) $key), $forbidden, true))
            ->toArray();
    }
}
