<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_post');
    }

    public function view(User $user, Post $post): bool
    {
        if (!$user->can('view_post')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        // Nếu là tác giả
        if ($post->created_by === $user->id) {
            return true;
        }

        // Nếu status = VISIBLE và chưa hết hạn
        if ($post->status === 'VISIBLE' && (!$post->visible_until || $post->visible_until->isFuture())) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_post') && !$user->is_locked;
    }

    public function update(User $user, Post $post): bool
    {
        if (!$user->can('update_post')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        return $user->id === $post->created_by;
    }

    public function delete(User $user, Post $post): bool
    {
        if (!$user->can('delete_post')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->id === $post->created_by;
    }
}
