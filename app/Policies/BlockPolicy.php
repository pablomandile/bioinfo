<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    public function update(User $user, Block $block): bool
    {
        return $block->page->user_id === $user->id;
    }

    public function delete(User $user, Block $block): bool
    {
        return $block->page->user_id === $user->id;
    }
}
