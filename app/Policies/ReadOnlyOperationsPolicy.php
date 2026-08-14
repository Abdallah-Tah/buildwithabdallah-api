<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ReadOnlyOperationsPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isOperationsAdmin() ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOperationsAdmin();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->isOperationsAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }
}
