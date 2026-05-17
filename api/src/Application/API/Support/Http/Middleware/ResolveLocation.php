<?php

namespace Application\API\Support\Http\Middleware;

use Closure;
use Domain\Support\Singletons\LocationContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveLocation
{
    public function __construct(private LocationContext $context) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locationId = $request->session()->get('location_id')
            ?? $request->header('X-Location-ID')
            ?? $request->route('location_id')?->id;

        if ($locationId !== null) {
            $this->context->setLocationKey($locationId);
        }

        return $next($request);
    }
}
