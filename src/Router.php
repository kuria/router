<?php declare(strict_types=1);

namespace Kuria\Router;

use Kuria\Router\Result\Match;
use Kuria\Router\Result\MethodNotAllowed;
use Kuria\Router\Result\NotFound;
use Kuria\Router\Result\ResultInterface;
use Kuria\Router\Route\Route;
use Kuria\Router\Route\RouteCollector;
use Kuria\Url\Url;

class Router
{
    /** @var Route[] name-indexed */
    private $routes = [];

    /** @var Url */
    private $baseUrl;

    /** @var RouteCollector|null */
    private $routeCollector;

    function __construct(?Url $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? static::getDefaultBaseUrl();
    }

    /**
     * Get the configured base URL
     */
    function getBaseUrl(): Url
    {
        return $this->baseUrl;
    }

    /**
     * Set base URL to be used during route matching and URL generation
     */
    function setBaseUrl(Url $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    function setRouteCollector(RouteCollector $routeCollector): void
    {
        $this->routeCollector = $routeCollector;
    }

    /**
     * Get configured routes
     *
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
        $this->routes = $routes + $this->routes;
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

        $this->routes = $collector->getRoutes() + $this->routes;
    }

    /**
     * See if a route exists
     */
    function hasRoute(string $routeName): bool
    {
        return isset($this->routes[$routeName]);
    }

    /**
     * Match routes against the given request
     */
    function match(string $method, Url $url): ResultInterface
    {
        $subject = $this->createSubjectFromUrl($method, $url);

        // try to find a match
        foreach ($this->routes as $route) {
            $match = $route->match($subject);

            if ($match !== null) {
                // success
                return new Match($route, $match);
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
                    return new Match($route, $match);
                }
            }
        }

        // no match, see if any other methods would match
        $allowedMethodMap = [];

        foreach ($this->routes as $route) {
            if ($route->match($subject, true) !== null && ($allowedMethods = $route->getAllowedMethods())) {
                $allowedMethodMap += array_flip($allowedMethods);
            }
        }

        if ($allowedMethodMap) {
            // method not allowed
            return new MethodNotAllowed(array_keys($allowedMethodMap));
        }

        // not found
        return new NotFound();
    }

    /**
     * Generate URL for the given route
     *
     * @throws \OutOfBoundsException if no such route exists
     * @throws \InvalidArgumentException if some parameters are missing or invalid
     */
    function generate(string $routeName, array $parameters = [], bool $checkRequirements = true): Url
    {
        if (!isset($this->routes[$routeName])) {
            throw new \OutOfBoundsException(sprintf('There is no route named "%s"', $routeName));
        }

        return $this->routes[$routeName]->generate($this->baseUrl, $parameters, $checkRequirements);
    }

    private function getRouteCollector(): RouteCollector
    {
        return $this->routeCollector ?? ($this->routeCollector = new RouteCollector());
    }

    protected function createSubjectFromUrl(string $method, Url $url): Subject
    {
        $path = $url->getPath();

        // remove base URL path before matching
        $basePath = $this->baseUrl->getPath();
        $basePathLength = strlen($basePath);

        if ($basePathLength > 0 && strncmp($path, $basePath, $basePathLength) === 0) {
            $path = substr($path, $basePathLength);
        }

        return new Subject(
            $method,
            $url->getScheme(),
            $url->getHost(),
            $url->getPort(),
            rawurldecode($path),
            $url->getQuery()
        );
    }

    protected static function getDefaultBaseUrl(): Url
    {
        $url = Url::current();
        $url->setUser(null);
        $url->setPassword(null);
        $url->setPath('');
        $url->setQuery([]);
        $url->setFragment(null);

        return $url;
    }
}
