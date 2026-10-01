<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire sends the browser's live connection id with every request, so
 * broadcast(...)->toOthers() can leave that browser out. Before the connection
 * is up, the id goes out as "undefined", and Pusher rejects the whole event:
 * nobody hears about it. Dropping an id that is not one sends the event to
 * everyone instead.
 */
class IgnoreInvalidSocketId
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $socket = $request->header('X-Socket-ID');

        if ($socket !== null && preg_match('/\A\d+\.\d+\z/', $socket) !== 1) {
            $request->headers->remove('X-Socket-ID');
        }

        return $next($request);
    }
}
