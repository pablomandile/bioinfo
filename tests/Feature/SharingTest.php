<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Page}
     */
    private function makePage(string $username, string $status = 'published'): array
    {
        $user = User::factory()->create(['username' => $username]);
        $page = Page::create([
            'user_id' => $user->id,
            'slug' => 'home',
            'title' => 'Creator',
            'bio' => 'Mi bio de prueba',
            'layout' => 'list',
            'status' => $status,
            'is_primary' => true,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return [$user, $page];
    }

    public function test_qr_svg_is_served_for_a_published_page(): void
    {
        $this->makePage('creator');

        $response = $this->get('/creator/qr.svg');

        $response->assertOk();
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_qr_is_not_available_for_a_draft_page(): void
    {
        $this->makePage('drafter', 'draft');

        $this->get('/drafter/qr.svg')->assertNotFound();
    }

    public function test_qr_returns_404_for_unknown_username(): void
    {
        $this->get('/nadie/qr.svg')->assertNotFound();
    }

    public function test_public_page_exposes_open_graph_meta_server_side(): void
    {
        $this->makePage('creator');

        $response = $this->get('/creator');

        $response->assertOk();
        $response->assertSee('property="og:title"', false);
        $response->assertSee('Creator', false);
        $response->assertSee('Mi bio de prueba', false);
    }

    public function test_draft_preview_is_marked_noindex(): void
    {
        [$user] = $this->makePage('owner', 'draft');

        $this->actingAs($user)
            ->get('/owner')
            ->assertOk()
            ->assertSee('name="robots" content="noindex"', false);
    }
}
