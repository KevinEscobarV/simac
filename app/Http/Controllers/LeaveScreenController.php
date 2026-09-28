<?php

namespace App\Http\Controllers;

use App\Models\Projection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LeaveScreenController extends Controller
{
    /**
     * A screen tab is closing or reloading. The browser sends this as it
     * leaves (a beacon), so it is a plain route rather than a Livewire call.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'screen' => ['required', 'string'],
        ]);

        Projection::forgetScreen(Str::limit($validated['screen'], 40, ''));

        return response()->noContent();
    }
}
