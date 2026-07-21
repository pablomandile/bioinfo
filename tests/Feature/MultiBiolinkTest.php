<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MultiBiolinkTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['username' => 'creator']);
    }

    private function makePage(User $user, bool $primary, string $slug): Page
    {
        return Page::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'title' => 'Biolink',
            'layout' => 'list',
            'status' => 'published',
            'is_primary' => $primary,
            'published_at' => now(),
        ]);
    }

    public function test_user_can_create_a_biolink(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/dashboard/pages')->assertRedirect();

        $this->assertDatabaseCount('pages', 1);
        $this->assertTrue($user->pages()->first()->is_primary);
    }

    public function test_second_biolink_is_not_primary(): void
    {
        $user = $this->user();
        $this->makePage($user, true, 'home');

        $this->actingAs($user)->post('/dashboard/pages');

        $this->assertSame(2, $user->pages()->count());
        $this->assertSame(1, $user->pages()->where('is_primary', true)->count());
    }

    public function test_biolinks_are_capped_at_four(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 4; $i++) {
            $this->makePage($user, $i === 0, "b{$i}");
        }

        $this->actingAs($user)->post('/dashboard/pages')->assertSessionHasErrors('pages');

        $this->assertSame(4, $user->pages()->count());
    }

    public function test_making_a_page_primary_unsets_the_others(): void
    {
        $user = $this->user();
        $a = $this->makePage($user, true, 'a');
        $b = $this->makePage($user, false, 'b');

        $this->actingAs($user)->patch("/dashboard/pages/{$b->id}/primary")->assertRedirect();

        $this->assertFalse($a->fresh()->is_primary);
        $this->assertTrue($b->fresh()->is_primary);
    }

    public function test_deleting_the_primary_promotes_another(): void
    {
        $user = $this->user();
        $a = $this->makePage($user, true, 'a');
        $b = $this->makePage($user, false, 'b');

        $this->actingAs($user)->delete("/dashboard/pages/{$a->id}")->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('pages', ['id' => $a->id]);
        $this->assertTrue($b->fresh()->is_primary);
    }

    public function test_cannot_delete_the_only_biolink(): void
    {
        $user = $this->user();
        $a = $this->makePage($user, true, 'a');

        $this->actingAs($user)->delete("/dashboard/pages/{$a->id}")->assertSessionHasErrors('pages');

        $this->assertDatabaseHas('pages', ['id' => $a->id]);
    }

    public function test_secondary_page_renders_by_slug(): void
    {
        $user = $this->user();
        $this->makePage($user, true, 'home');
        $secondary = $this->makePage($user, false, 'tienda');
        $secondary->update(['title' => 'Mi Tienda']);

        $this->get('/creator/tienda')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/Show')->where('profile.title', 'Mi Tienda'));
    }

    public function test_slug_must_be_unique_per_user(): void
    {
        $user = $this->user();
        $this->makePage($user, true, 'home');
        $secondary = $this->makePage($user, false, 'tienda');

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$secondary->id}", ['slug' => 'home'])
            ->assertStatus(422);
    }
}
