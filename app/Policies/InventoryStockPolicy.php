<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventoryStockPolicy extends FilamentResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'kasir'], true);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }
}
