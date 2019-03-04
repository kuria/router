<?php declare(strict_types=1);

namespace Kuria\Router\Result;

use Kuria\Router\Route\Route;
use Kuria\Router\Subject;

/**
 * Successful route match
 */
class Match extends Result
{
    /** @var Route */
    private $route;

    /** @var array */
    private $parameters;

    function __construct(Subject $subject, Route $route, array $parameters)
    {
        parent::__construct($subject);

        $this->route = $route;
        $this->parameters = $parameters;
    }

    function getRoute(): Route
    {
        return $this->route;
    }

    function getParameters(): array
    {
        return $this->parameters;
    }
}
