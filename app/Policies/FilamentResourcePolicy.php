<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilamentResourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view the list
    }

    public function view(User $user, $model): bool
    {
        if ($user->role === 'admin') {
            return true; // Admin can view everything
        }

        // Kasir can only view Customer and Order resources
        return in_array(class_basename($model), ['Customer', 'Order']);
    }

    public function create(User $user): bool
    {
        if ($user->role === 'admin') {
            return true; // Admin can create everything
        }

        // Kasir can only create Customer and Order resources
        $resource = request()->route()->parameter('resource');
        return in_array($resource, ['customers', 'orders']);
    }

    public function update(User $user, $model): bool
    {
        if ($user->role === 'admin') {
            return true; // Admin can update everything
        }

        // Kasir can only update Customer and Order resources
        return in_array(class_basename($model), ['Customer', 'Order']);
    }

    public function delete(User $user, $model): bool
    {
        if ($user->role === 'admin') {
            return true; // Admin can delete everything
        }

        // Kasir can only delete Customer and Order resources
        return in_array(class_basename($model), ['Customer', 'Order']);
    }
} 