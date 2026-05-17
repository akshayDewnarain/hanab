<?php

namespace Application\API\Support\Http\Middleware;

use Closure;
use Domain\Support\Helpers\ApiResponse;
use Domain\Support\Singletons\LocationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequireLocation
{
    public function __construct(private LocationContext $context) {}

    public function handle(Request $request, Closure $next): JsonResponse
    {
        if ($this->context->getLocationKey() === null) {
            return ApiResponse::error(__('LOCATION_REQUIRED'), 400);
        }

        return $next($request);
    }
}
