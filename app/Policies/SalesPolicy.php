<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SalesPolicy extends InventoryStockPolicy
{
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }
}
