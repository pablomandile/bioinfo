<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Settings\RegistrationSettings;
use App\Settings\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(SiteSettings $site, RegistrationSettings $registration): Response
    {
        return Inertia::render('admin/Settings', [
            'settings' => [
                'siteName' => $site->name,
                'registrationOpen' => $registration->open,
            ],
        ]);
    }

    public function update(Request $request, SiteSettings $site, RegistrationSettings $registration): RedirectResponse
    {
        $data = $request->validate([
            'siteName' => ['required', 'string', 'max:120'],
            'registrationOpen' => ['required', 'boolean'],
        ]);

        $site->name = $data['siteName'];
        $site->save();

        $registration->open = $data['registrationOpen'];
        $registration->save();

        return back();
    }
}
