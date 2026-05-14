<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Credential extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'username',
        'email',
        'password_encrypted',
        'url',
        'notes_encrypted',
        'custom_fields_encrypted',
        'is_favorite',
        'tags',
        'last_accessed_at',
        'password_changed_at',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'tags' => 'array',
        'last_accessed_at' => 'datetime',
        'password_changed_at' => 'datetime',
        // `password_encrypted`, `notes_encrypted`, and `custom_fields_encrypted` are
        // managed by EncryptionService — not cast here, so the ciphertext stays raw
        // and we control encrypt/decrypt explicitly. This keeps decryption out of
        // accidental serialization paths.
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class)->orderByDesc('changed_at');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('is_favorite', true);
    }

    public function scopeInCategory(Builder $query, ?int $categoryId): Builder
    {
        return $categoryId ? $query->where('category_id', $categoryId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
              ->orWhere('username', 'like', $like)
              ->orWhere('email', 'like', $like)
              ->orWhere('url', 'like', $like);
        });
    }

    public function scopeWithTag(Builder $query, ?string $tag): Builder
    {
        if (! $tag) {
            return $query;
        }

        // JSON_CONTAINS works with Laravel's JSON column on MySQL.
        return $query->whereJsonContains('tags', $tag);
    }
}
