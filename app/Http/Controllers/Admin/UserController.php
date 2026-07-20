<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('roles')
            ->withCount('pages')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'isActive' => $user->is_active,
                'role' => $user->isAdmin() ? Role::Admin->value : Role::User->value,
                'pagesCount' => $user->pages_count,
            ]);

        return Inertia::render('admin/Users/Index', [
            'users' => $users,
            'roles' => collect(Role::cases())->map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()])->values(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::enum(Role::class)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // BR-1.6: siempre debe quedar al menos un admin activo.
        $demotingAdmin = isset($data['role']) && $user->isAdmin() && $data['role'] !== Role::Admin->value;
        $deactivatingAdmin = array_key_exists('is_active', $data) && ! $data['is_active'] && $user->isAdmin();

        if (($demotingAdmin || $deactivatingAdmin) && $this->activeAdminCount() <= 1) {
            return back()->withErrors(['user' => 'Debe existir al menos un administrador activo.']);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if (array_key_exists('is_active', $data)) {
            $user->update(['is_active' => $data['is_active']]);
        }

        return back();
    }

    private function activeAdminCount(): int
    {
        return User::role(Role::Admin->value)->where('is_active', true)->count();
    }
}
