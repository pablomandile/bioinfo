<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventType;
use App\Enums\PageStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\Block;
use App\Models\Page;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'admins' => User::role(Role::Admin->value)->count(),
                'pages' => Page::count(),
                'publishedPages' => Page::where('status', PageStatus::Published->value)->count(),
                'blocks' => Block::count(),
                'views' => (int) AnalyticsDaily::where('type', EventType::PageView->value)->sum('count'),
            ],
        ]);
    }
}
