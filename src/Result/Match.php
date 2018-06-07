<?php declare(strict_types=1);

namespace Kuria\Router\Result;

use Kuria\Router\Route\RouteInterface;

/**
 * Successful route match
 */
class Match implements ResultInterface
{
    /** @var RouteInterface */
    private $route;
    /** @var array */
    private $parameters;

    function __construct(RouteInterface $route, array $parameters)
    {
        $this->route = $route;
        $this->parameters = $parameters;
    }

    function getRoute(): RouteInterface
    {
        return $this->route;
    }

    function getParameters(): array
    {
        return $this->parameters;
    }
}
