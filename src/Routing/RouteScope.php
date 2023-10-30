<?php
declare(strict_types=1);

namespace Cake\Routing;

use Closure;

/**
 * Builder for routing scopes that allows deferred evaluation
 * of scope callbacks.
 *
 * @internal
 */
class RouteScope
{
    /**
     * Constructor
     *
     * @param \Cake\Routing\RouterBuilder $builder The route builder to be used when resolving this scope.
     * @param \Closure $callback The closure containing the routes in a scope.
     */
    public function __construct(
        private RouteBuilder $builder,
        private Closure $callback,
    ) {
    }

    /**
     * Check if this scope matches the provided path
     *
     * @param string $path The path to compare with
     * @return bool
     */
    public function matchesPath(string $path): bool
    {
        $scopePath = $this->builder->path();

        return str_starts_with($path, $scopePath);
    }

    /**
     * Check if this scope's builder defaults matches the provided URL array.
     *
     * Scopes with no parameters match every scope as the routes within the scope
     * are undefined and thus the scope could create routes that match.
     *
     * @array $url The url array being checked.
     * @return bool
     */
    public function matchesUrl(array $url): bool
    {
        $defaults = $this->builder->params();
        // If the defaults are empty, the URL could match routes
        // defined by the scope.
        if (empty($defaults)) {
            return true;
        }

        $fullMatch = array_intersect_key($url, $defaults) === $defaults;
        if ($fullMatch) {
            return $fullMatch;
        }
        // If there is no prefix key bail,
        // The prefix key is special in that it can have partial matches.
        if (!isset($url['prefix']) || !isset($defaults['prefix'])) {
            return false;
        }

        return str_starts_with($url['prefix'], $defaults['prefix']);
    }

    /**
     * Get the path prefix for this scope
     *
     * @return string
     */
    public function path(): string
    {
        return $this->builder->path();
    }

    /**
     * Resolve the deferred scope and add all routes
     * contained in the scope callback
     */
    public function resolve(): void
    {
        $callback = $this->callback;
        $callback($this->builder);
    }
}
