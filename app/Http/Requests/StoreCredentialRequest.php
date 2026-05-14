<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:8192'],
            'url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'is_favorite' => ['nullable', 'boolean'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.key' => ['nullable', 'string', 'max:128'],
            'custom_fields.*.value' => ['nullable', 'string', 'max:4096'],
        ];
    }

    /**
     * Normalize custom_fields: strip empty rows, keep order, ensure array shape.
     */
    public function customFields(): ?array
    {
        $fields = $this->input('custom_fields', []);
        if (! is_array($fields)) return null;

        $cleaned = [];
        foreach ($fields as $row) {
            if (! is_array($row)) continue;
            $key = trim((string) ($row['key'] ?? ''));
            $value = (string) ($row['value'] ?? '');
            if ($key === '' && $value === '') continue;
            $cleaned[] = ['key' => $key, 'value' => $value];
        }

        return $cleaned ?: null;
    }

    public function tagsArray(): ?array
    {
        $tags = $this->input('tags');
        if (! is_array($tags)) return null;
        $tags = array_values(array_filter(array_map('trim', $tags), fn ($t) => $t !== ''));
        return $tags ?: null;
    }
}
