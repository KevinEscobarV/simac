<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a plain http request to the same page over https, when the app is
 * served over https (APP_URL says so). Over http the page loads, but the
 * session cookie only travels over https, so signing in fails with "page
 * expired". Some browsers open an address typed without a scheme over http.
 */
class RedirectToHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->secure() || ! str_starts_with((string) config('app.url'), 'https://')) {
            return $next($request);
        }

        return redirect()->to('https://'.$request->getHttpHost().$request->getRequestUri(), 301);
    }
}
