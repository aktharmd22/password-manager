<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

/**
 * Single-user authorization. Authenticated users may do anything; if you
 * later add a multi-user mode, gate on `owner_id` here.
 */
class CredentialPolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, Credential $credential): bool   { return true; }
    public function create(User $user): bool    { return true; }
    public function update(User $user, Credential $credential): bool { return true; }
    public function delete(User $user, Credential $credential): bool { return true; }
    public function restore(User $user, Credential $credential): bool { return true; }
    public function reveal(User $user, Credential $credential): bool { return true; }
    public function copy(User $user, Credential $credential): bool   { return true; }
}
