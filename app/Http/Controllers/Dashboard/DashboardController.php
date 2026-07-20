<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $pages = $user->pages()
            ->orderByDesc('is_primary')
            ->withCount('blocks')
            ->get()
            ->map(fn (Page $page) => [
                'id' => $page->id,
                'title' => $page->title ?: $user->name,
                'slug' => $page->slug,
                'status' => $page->status->value,
                'isPrimary' => $page->is_primary,
                'blocksCount' => $page->blocks_count,
                'publicUrl' => url('/'.$user->username),
                'editUrl' => route('pages.edit', $page),
            ]);

        return Inertia::render('Dashboard', [
            'pages' => $pages,
            'username' => $user->username,
        ]);
    }
}
