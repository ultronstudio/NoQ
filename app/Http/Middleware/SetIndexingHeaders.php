<?php

namespace App\Http\Middleware;

use App\Support\Metadata;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetIndexingHeaders
{
    public function __construct(private readonly Metadata $metadata) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', $response->isSuccessful()
            ? $this->metadata->robots($request)
            : 'noindex, nofollow');

        return $response;
    }
}
