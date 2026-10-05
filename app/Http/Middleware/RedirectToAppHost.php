<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a request that reached the site under another name (www., the
 * hosting's other aliases) to the app's own address, when the app is served
 * over https (APP_URL says so). Passkeys only work on the host they were made
 * for, and one address keeps sessions and bookmarks in one place. It runs
 * before RedirectToHttps, so http://www. gets there in a single step.
 */
class RedirectToAppHost
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = (string) config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        if (! str_starts_with($appUrl, 'https://') || ! is_string($appHost) || $request->getHost() === $appHost) {
            return $next($request);
        }

        return redirect()->to('https://'.$appHost.$request->getRequestUri(), 301);
    }
}
