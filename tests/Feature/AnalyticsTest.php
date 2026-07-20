<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Page}
     */
    private function publishedPage(): array
    {
        $user = User::factory()->create(['username' => 'creator']);
        $page = Page::create([
            'user_id' => $user->id,
            'slug' => 'home',
            'title' => 'Creator',
            'layout' => 'list',
            'status' => 'published',
            'is_primary' => true,
            'published_at' => now(),
        ]);

        return [$user, $page];
    }

    public function test_guest_visit_records_a_page_view(): void
    {
        [, $page] = $this->publishedPage();

        $this->get('/creator')->assertOk();

        $this->assertDatabaseHas('analytics_events', ['page_id' => $page->id, 'type' => 'page_view']);
        $this->assertSame(1, AnalyticsEvent::where('type', 'page_view')->count());
    }

    public function test_repeated_view_is_deduplicated(): void
    {
        $this->publishedPage();

        $this->get('/creator');
        $this->get('/creator');

        $this->assertSame(1, AnalyticsEvent::where('type', 'page_view')->count());
    }

    public function test_owner_visit_is_not_recorded(): void
    {
        [$user] = $this->publishedPage();

        $this->actingAs($user)->get('/creator')->assertOk();

        $this->assertSame(0, AnalyticsEvent::where('type', 'page_view')->count());
    }

    public function test_link_click_is_recorded(): void
    {
        [, $page] = $this->publishedPage();
        $block = $page->blocks()->create(['type' => 'link', 'position' => 1, 'data' => ['label' => 'X', 'url' => 'https://example.com/t']]);

        $this->get("/go/{$block->public_id}")->assertRedirect('https://example.com/t');

        $this->assertDatabaseHas('analytics_events', ['block_id' => $block->id, 'type' => 'link_click']);
    }

    public function test_hidden_block_click_is_not_recorded(): void
    {
        [, $page] = $this->publishedPage();
        $block = $page->blocks()->create([
            'type' => 'link',
            'position' => 1,
            'is_visible' => false,
            'data' => ['label' => 'X', 'url' => 'https://example.com/t'],
        ]);

        $this->get("/go/{$block->public_id}")->assertRedirect('https://example.com/t');

        $this->assertSame(0, AnalyticsEvent::where('type', 'link_click')->count());
    }

    public function test_rollup_aggregates_events(): void
    {
        [, $page] = $this->publishedPage();
        AnalyticsEvent::create(['page_id' => $page->id, 'type' => 'page_view', 'created_at' => now()]);
        AnalyticsEvent::create(['page_id' => $page->id, 'type' => 'page_view', 'created_at' => now()]);

        $this->artisan('analytics:rollup')->assertSuccessful();

        $this->assertDatabaseHas('analytics_daily', ['page_id' => $page->id, 'type' => 'page_view', 'count' => 2]);
    }

    public function test_owner_sees_the_analytics_page(): void
    {
        [$user, $page] = $this->publishedPage();

        $this->actingAs($user)
            ->get("/dashboard/pages/{$page->id}/analytics")
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia->component('analytics/Show')->has('totals'));
    }

    public function test_non_owner_cannot_see_the_analytics_page(): void
    {
        [, $page] = $this->publishedPage();
        $other = User::factory()->create(['username' => 'other']);

        $this->actingAs($other)
            ->get("/dashboard/pages/{$page->id}/analytics")
            ->assertForbidden();
    }
}
