<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Page}
     */
    private function userWithPage(string $username = 'owner'): array
    {
        $user = User::factory()->create(['username' => $username]);
        $page = Page::create([
            'user_id' => $user->id,
            'slug' => 'home',
            'title' => 'Owner',
            'layout' => 'list',
            'status' => 'draft',
            'is_primary' => true,
        ]);

        return [$user, $page];
    }

    public function test_owner_can_open_the_editor(): void
    {
        [$user, $page] = $this->userWithPage();

        $this->actingAs($user)
            ->get("/dashboard/pages/{$page->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia->component('editor/Edit')->where('page.id', $page->id));
    }

    public function test_non_owner_cannot_open_the_editor(): void
    {
        [, $page] = $this->userWithPage();
        $other = User::factory()->create(['username' => 'other']);

        $this->actingAs($other)
            ->get("/dashboard/pages/{$page->id}/edit")
            ->assertForbidden();
    }

    public function test_owner_can_create_and_update_a_block(): void
    {
        [$user, $page] = $this->userWithPage();

        $this->actingAs($user)
            ->postJson("/dashboard/pages/{$page->id}/blocks", ['type' => 'link'])
            ->assertCreated()
            ->assertJsonPath('type', 'link');

        $block = $page->blocks()->firstOrFail();

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}", [
                'data' => ['label' => 'Hola', 'url' => 'https://example.com'],
            ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Hola');

        $this->assertSame('https://example.com', $block->fresh()->data['url']);
    }

    public function test_update_block_validates_the_payload(): void
    {
        [$user, $page] = $this->userWithPage();
        $this->actingAs($user)->postJson("/dashboard/pages/{$page->id}/blocks", ['type' => 'link']);
        $block = $page->blocks()->firstOrFail();

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}", [
                'data' => ['label' => 'x', 'url' => 'no-es-una-url'],
            ])
            ->assertStatus(422);
    }

    public function test_link_block_accepts_mailto_and_tel(): void
    {
        [$user, $page] = $this->userWithPage();
        $this->actingAs($user)->postJson("/dashboard/pages/{$page->id}/blocks", ['type' => 'link']);
        $block = $page->blocks()->firstOrFail();

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}", [
                'data' => ['label' => 'Escribime', 'url' => 'mailto:pablo.mandile@gmail.com'],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}", [
                'data' => ['label' => 'Llamame', 'url' => 'tel:+5491112345678'],
            ])
            ->assertOk();

        // mailto: con correo inválido y esquemas no permitidos siguen rechazándose.
        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}", [
                'data' => ['label' => 'Malo', 'url' => 'mailto:no-es-un-correo'],
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}", [
                'data' => ['label' => 'Malo', 'url' => 'javascript:alert(1)'],
            ])
            ->assertStatus(422);
    }

    public function test_reorder_persists_positions(): void
    {
        [$user, $page] = $this->userWithPage();
        $a = $page->blocks()->create(['type' => 'heading', 'position' => 1, 'data' => ['text' => 'A']]);
        $b = $page->blocks()->create(['type' => 'heading', 'position' => 2, 'data' => ['text' => 'B']]);

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/reorder", ['ids' => [$b->public_id, $a->public_id]])
            ->assertNoContent();

        $this->assertSame(1, $b->fresh()->position);
        $this->assertSame(2, $a->fresh()->position);
    }

    public function test_owner_can_delete_a_block(): void
    {
        [$user, $page] = $this->userWithPage();
        $block = $page->blocks()->create(['type' => 'heading', 'position' => 1, 'data' => ['text' => 'A']]);

        $this->actingAs($user)
            ->deleteJson("/dashboard/pages/{$page->id}/blocks/{$block->public_id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('blocks', ['id' => $block->id]);
    }

    public function test_publishing_requires_a_title_or_a_block(): void
    {
        [$user, $page] = $this->userWithPage();
        $page->update(['title' => null]);

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}", ['status' => 'published'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}", ['title' => 'Mi perfil', 'status' => 'published'])
            ->assertOk();

        $this->assertSame('published', $page->fresh()->status->value);
    }

    public function test_scoped_binding_rejects_a_foreign_block(): void
    {
        [$user, $page] = $this->userWithPage();
        [, $otherPage] = $this->userWithPage('foreign');
        $foreignBlock = $otherPage->blocks()->create(['type' => 'heading', 'position' => 1, 'data' => ['text' => 'X']]);

        $this->actingAs($user)
            ->patchJson("/dashboard/pages/{$page->id}/blocks/{$foreignBlock->public_id}", [
                'data' => ['text' => 'hack'],
            ])
            ->assertNotFound();
    }

    public function test_admin_can_edit_another_users_page(): void
    {
        $this->seed(RoleSeeder::class);
        [, $page] = $this->userWithPage();
        $admin = User::factory()->create(['username' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get("/dashboard/pages/{$page->id}/edit")
            ->assertOk();
    }
}
