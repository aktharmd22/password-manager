<?php

namespace App\Http\Requests;

class UpdateCredentialRequest extends StoreCredentialRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // On update password may be left empty to keep the existing one.
            'password' => ['nullable', 'string', 'max:8192'],
        ]);
    }
}
