<?php

namespace App\Policies;

use App\Models\SocialLink;
use App\Models\User;

class SocialLinkPolicy
{
    public function update(User $user, SocialLink $socialLink): bool
    {
        return $socialLink->page->user_id === $user->id;
    }

    public function delete(User $user, SocialLink $socialLink): bool
    {
        return $socialLink->page->user_id === $user->id;
    }
}
