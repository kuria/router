<?php declare(strict_types=1);

namespace Kuria\Router\Route;

class RouteCollector
{
    /** @var PatternCompiler */
    private $patternCompiler;

    /** @var RouteBuilder[] */
    private $builders = [];

    function __construct(?PatternCompiler $patternCompiler = null)
    {
        $this->patternCompiler = $patternCompiler ?? new PatternCompiler();
    }

    /**
     * Add a route
     */
    function add(string $routeName): RouteBuilder
    {
        $builder = new RouteBuilder($this->patternCompiler, $routeName);

        $this->builders[$routeName] = $builder;

        return $builder;
    }

    /**
     * Add a route that matches GET requests
     */
    function get(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['GET']);
    }

    /**
     * Add a route that matches HEAD requests
     */
    function head(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['HEAD']);
    }

    /**
     * Add a route that matches POST requests
     */
    function post(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['POST']);
    }

    /**
     * Add a route that matches PUT requests
     */
    function put(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['PUT']);
    }

    /**
     * Add a route that matches DELETE requests
     */
    function delete(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['DELETE']);
    }

    /**
     * Add a route that matches OPTIONS requests
     */
    function options(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['OPTIONS']);
    }

    /**
     * Add a route that matches PATCH requests
     */
    function patch(string $routeName): RouteBuilder
    {
        return $this->add($routeName)->allowedMethods(['PATCH']);
    }

    /**
     * Add a variant of an existing route
     *
     * Returns a cloned builder of the specified route.
     */
    function addVariant(string $existingRouteName, string $newRouteName): RouteBuilder
    {
        return $this->builders[$newRouteName] = $this->getBuilder($existingRouteName)->copy($newRouteName);
    }

    /**
     * Add a group of routes with common prefixes
     *
     * Callback signature: (RouteCollector $c)
     */
    function addGroup(string $namePrefix, string $pathPrefix, callable $callback): void
    {
        $collector = new static($this->patternCompiler);

        $callback($collector);

        foreach ($collector->getBuilders() as $name => $builder) {
            $builder = $builder->copy($namePrefix . $builder->getName());
            $builder->prependPath($pathPrefix);

            $this->builders[$namePrefix . $name] = $builder;
        }
    }

    /**
     * See if a route is defined
     */
    function hasBuilder(string $routeName): bool
    {
        return isset($this->builders[$routeName]);
    }

    /**
     * Get builder for the given route
     *
     * @throws \OutOfBoundsException if no such route is defined
     */
    function getBuilder($routeName): RouteBuilder
    {
        if (!isset($this->builders[$routeName])) {
            throw new \OutOfBoundsException(sprintf('Route "%s" is not defined', $routeName));
        }

        return $this->builders[$routeName];
    }

    /**
     * Remove route definition
     */
    function removeBuilder(string $routeName): void
    {
        unset($this->builders[$routeName]);
    }

    /**
     * Get all configured builders
     *
     * @return RouteBuilder[] name-indexed
     */
    function getBuilders(): array
    {
        return $this->builders;
    }

    /**
     * Build routes
     *
     * @throws \RuntimeException if any of the routes cannot be built
     * @return Route[] name-indexed
     */
    function getRoutes(): array
    {
        $routes = [];

        foreach ($this->builders as $name => $builder) {
            try {
                $routes[$name] = $builder->build();
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf('Failed when building route "%s" - %s', $name, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        return $routes;
    }

    /**
     * Remove all defined routes
     */
    function clear(): void
    {
        $this->builders = [];
    }
}
