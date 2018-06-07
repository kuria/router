<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\Router\Subject;
use Kuria\Url\Url;

interface RouteInterface
{
    /**
     * Get route name
     */
    function getName(): string;

    /**
     * @return string[]|null
     */
    function getAllowedMethods(): ?array;

    /**
     * Attempt to match the given subject
     *
     * Returns match attributes or NULL on failure.
     */
    function match(Subject $subject, bool $ignoreMethod = false): ?array;

    /**
     * Generate an URL
     *
     * @throws \InvalidArgumentException if the parameters are not valid
     */
    function generate(Url $baseUrl, array $parameters): Url;

    /**
     * Dump information about the route
     */
    function dump(): string;
}
