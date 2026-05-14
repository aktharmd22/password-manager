<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    // Audit logs are append-only; we manage created_at on insert ourselves and never
    // touch updated_at. Disabling timestamps prevents Eloquent from auto-managing them.
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'credential_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class)->withTrashed();
    }

    public function getActionLabelAttribute(): string
    {
        return config('vault.audit_actions')[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }
}
