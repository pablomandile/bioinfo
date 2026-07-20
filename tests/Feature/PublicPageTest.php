<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(User $user, array $overrides = []): Page
    {
        return Page::create(array_merge([
            'user_id' => $user->id,
            'slug' => 'home',
            'title' => 'Creator',
            'bio' => 'Hola',
            'layout' => 'list',
            'status' => 'published',
            'is_primary' => true,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_it_renders_a_published_public_page(): void
    {
        $user = User::factory()->create(['username' => 'creator']);
        $page = $this->makePage($user);
        $page->blocks()->create(['type' => 'link', 'position' => 1, 'data' => ['label' => 'Sitio', 'url' => 'https://example.com']]);

        $this->get('/creator')
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('public/Show')
                ->where('profile.username', 'creator')
                ->has('blocks', 1)
                ->has('theme.cssVars'));
    }

    public function test_it_returns_404_for_a_draft_page_to_guests(): void
    {
        $user = User::factory()->create(['username' => 'drafter']);
        $this->makePage($user, ['status' => 'draft', 'published_at' => null]);

        $this->get('/drafter')->assertNotFound();
    }

    public function test_owner_can_preview_their_draft_page(): void
    {
        $user = User::factory()->create(['username' => 'owner']);
        $this->makePage($user, ['status' => 'draft', 'published_at' => null]);

        $this->actingAs($user)
            ->get('/owner')
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia->where('isOwnerPreview', true));
    }

    public function test_it_redirects_a_link_block_click_to_its_destination(): void
    {
        $user = User::factory()->create(['username' => 'clicker']);
        $page = $this->makePage($user);
        $block = $page->blocks()->create(['type' => 'link', 'position' => 1, 'data' => ['label' => 'X', 'url' => 'https://example.com/target']]);

        $this->get("/go/{$block->public_id}")->assertRedirect('https://example.com/target');
    }

    public function test_it_returns_404_for_unknown_username(): void
    {
        $this->get('/nadie-existe')->assertNotFound();
    }
}
