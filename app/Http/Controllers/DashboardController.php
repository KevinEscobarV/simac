<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The dashboard, or the post of whoever works elsewhere. Every way of
     * signing in ends here, so this is what sends each role to its post.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $home = $request->user()->homeRoute();

        if ($home !== 'dashboard') {
            return to_route($home);
        }

        return view('dashboard');
    }
}
