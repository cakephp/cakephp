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
        // TODO implement this.
        return true;
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
