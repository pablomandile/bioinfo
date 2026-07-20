<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function update(User $user, Page $page): bool
    {
        return $page->user_id === $user->id;
    }

    public function delete(User $user, Page $page): bool
    {
        return $page->user_id === $user->id;
    }
}
