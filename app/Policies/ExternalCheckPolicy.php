<?php

namespace App\Policies;

use App\Models\ExternalCheck;
use App\Models\User;

class ExternalCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExternalCheck $externalCheck): bool
    {
        return $user->isAdmin() || $externalCheck->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ExternalCheck $externalCheck): bool
    {
        return $this->view($user, $externalCheck);
    }

    public function delete(User $user, ExternalCheck $externalCheck): bool
    {
        return $this->view($user, $externalCheck);
    }
}
