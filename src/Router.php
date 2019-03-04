<?php declare(strict_types=1);

namespace Kuria\Router;

use Kuria\Router\Result\Match;
use Kuria\Router\Result\MethodNotAllowed;
use Kuria\Router\Result\NotFound;
use Kuria\Router\Result\Result;
use Kuria\Router\Route\Route;
use Kuria\Router\Route\RouteCollector;
use Kuria\Url\Url;

class Router
{
    /** @var Context|null */
    private $defaultContext;

    /** @var Route[] name-indexed */
    private $routes = [];

    /** @var RouteCollector|null */
    private $routeCollector;

    function getDefaultContext(): ?Context
    {
        return $this->defaultContext;
    }

    function setDefaultContext(?Context $defaultContext): void
    {
        $this->defaultContext  = $defaultContext;
    }

    function setRouteCollector(RouteCollector $routeCollector): void
    {
        $this->routeCollector = $routeCollector;
    }

    /**
     * @return Route[] name-indexed
     */
    function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Set routes
     *
     * The routes should be indexed by name.
     *
     * Ovewrites any previously configured routes.
     */
    function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    /**
     * Add routes
     *
     * The routes should be indexed by name.
     *
     * Overwites previously configured routes with the same key.
     */
    function addRoutes(array $routes): void
    {
        $this->routes = array_merge($this->routes, $routes);
    }

    /**
     * Define routes using a callback
     *
     * The callback should use the passed RouteCollector instance to define routes.
     */
    function defineRoutes(callable $callback): void
    {
        $collector = $this->getRouteCollector();
        $collector->clear();

        $callback($collector);

        $this->routes = array_merge($this->routes, $collector->getRoutes());
    }

    /**
     * See if a route exists
     */
    function hasRoute(string $routeName): bool
    {
        return isset($this->routes[$routeName]);
    }

    /**
     * Match routes against the given subject
     */
    function match(Subject $subject): Result
    {
        // try to find a match
        foreach ($this->routes as $route) {
            $match = $route->match($subject);

            if ($match !== null) {
                // success
                return new Match($subject, $route, $match);
            }
        }

        // attempt to fallback to a GET route if there is no matching HEAD route (as PHP itself supports this)
        if ($subject->method === 'HEAD') {
            $fallbackSubject = clone $subject;
            $fallbackSubject->method = 'GET';

            foreach ($this->routes as $route) {
                $match = $route->match($fallbackSubject);

                if ($match !== null) {
                    // success
                    return new Match($subject, $route, $match);
                }
            }
        }

        // no match, see if any other methods would match
        $allowedMethodMap = [];

        foreach ($this->routes as $route) {
            if ($route->match($subject, true) !== null && ($allowedMethods = $route->getMethods())) {
                $allowedMethodMap += array_flip($allowedMethods);
            }
        }

        if ($allowedMethodMap) {
            // method not allowed
            return new MethodNotAllowed($subject, array_keys($allowedMethodMap));
        }

        // not found
        return new NotFound($subject);
    }

    /**
     * Match routes against the given path
     *
     * - the path should not contain the base path
     * - scheme, host and port is derived from the given or default context
     *
     * @throws \LogicException if no context was given and there is no default context
     */
    function matchPath(string $method, string $path, ?Context $context = null): Result
    {
        if ($context === null) {
            $context = $this->requireDefaultContext();
        }

        return $this->match(new Subject($method, $context->scheme, $context->host, $context->port, $path));
    }

    /**
     * Generate URL for the given route
     *
     * If no context is given, the default context will be used instead.
     *
     * @throws \OutOfBoundsException if no such route exists
     * @throws \InvalidArgumentException if some parameters are missing or invalid
     * @throws \LogicException if no context was given and there is no default context
     */
    function generate(
        string $routeName,
        array $parameters = [],
        bool $checkRequirements = true,
        ?Context $context = null
    ): Url {
        if (!isset($this->routes[$routeName])) {
            throw new \OutOfBoundsException(sprintf('There is no route named "%s"', $routeName));
        }

        return $this->routes[$routeName]->generate(
            $context ?? $this->requireDefaultContext(),
            $parameters,
            $checkRequirements
        );
    }

    private function requireDefaultContext(): Context
    {
        if ($this->defaultContext === null) {
            throw new \LogicException('Default context is not defined');
        }

        return $this->defaultContext;
    }

    private function getRouteCollector(): RouteCollector
    {
        return $this->routeCollector ?? ($this->routeCollector = new RouteCollector());
    }
}
