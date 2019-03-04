<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\Router\Context;
use Kuria\Router\Subject;
use Kuria\Url\Url;

class Route
{
    /** @var string */
    private $name;

    /** @var array|null */
    private $methodMap;

    /** @var string|null */
    private $scheme;

    /** @var Pattern|null */
    private $hostPattern;

    /** @var int|null */
    private $port;

    /** @var PathPattern */
    private $pathPattern;

    /** @var array */
    private $defaults;

    /** @var array */
    private $attributes;

    /** @var array */
    private $knownParamMap;

    function __construct(
        string $name,
        ?array $methods,
        ?string $scheme,
        ?Pattern $hostPattern,
        ?int $port,
        PathPattern $pathPattern,
        array $defaults = [],
        array $attributes = []
    ) {
        $this->name = $name;
        $this->methodMap = $methods !== null ? array_flip($methods) : null;
        $this->scheme = $scheme;
        $this->hostPattern = $hostPattern;
        $this->port = $port;
        $this->pathPattern = $pathPattern;
        $this->defaults = $defaults;
        $this->attributes = $attributes;

        $this->knownParamMap = array_flip($pathPattern->getParameters());

        if ($hostPattern) {
            $this->knownParamMap += array_flip($hostPattern->getParameters());
        }
    }

    function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]|null
     */
    function getMethods(): ?array
    {
        return $this->methodMap !== null ? array_keys($this->methodMap) : null;
    }

    function getScheme(): ?string
    {
        return $this->scheme;
    }

    function getHostPattern(): ?Pattern
    {
        return $this->hostPattern;
    }

    function getPort(): ?int
    {
        return $this->port;
    }

    function getPathPattern(): PathPattern
    {
        return $this->pathPattern;
    }

    function getDefaults(): array
    {
        return $this->defaults;
    }

    function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Attempt to match the given subject
     *
     * Returns match attributes or NULL on failure.
     */
    function match(Subject $subject, bool $ignoreMethod = false): ?array
    {
        if (
            // method
            !$ignoreMethod && $this->methodMap !== null && !isset($this->methodMap[$subject->method])
            // scheme
            || $this->scheme !== null && $subject->scheme !== $this->scheme
            // host
            || $this->hostPattern !== null && ($hostAttrs = $this->hostPattern->match($subject->host)) === null
            // port
            || $this->port !== null && $subject->port !== $this->port
            // path
            || ($attrs = $this->pathPattern->match($subject->path)) === null
        ) {
            // failed
            return null;
        }

        // success
        return ($hostAttrs ?? []) + $attrs + $this->defaults;
    }

    /**
     * Generate an URL
     *
     * @throws \InvalidArgumentException if the parameters are not valid
     */
    function generate(Context $context, array $parameters = [], bool $checkRequirements = true): Url
    {
        $parameters += $this->defaults;

        $scheme = $this->scheme ?? $context->scheme;
        $host = $this->hostPattern !== null
            ? $this->hostPattern->generate($parameters, $checkRequirements)
            : $context->host;
        $port = $this->port ?? $context->getPortForScheme($scheme);

        return new Url(
            $scheme,
            $host,
            $scheme === 'https' && $port !== 443 || $scheme !== 'https' && $port !== 80
                ? $port // non-standard port
                : null,
            $context->basePath . $this->pathPattern->generate($parameters, $checkRequirements),
            $parameters
                ? array_diff_key($parameters, $this->knownParamMap) // set extra params as query
                : [],
            null,
            $scheme !== $context->scheme || $host !== $context->host || $port !== $context->port
                ? Url::ABSOLUTE // prefer absolute URL if scheme, host or port is different from the context
                : URL::RELATIVE
        );
    }

    /**
     * Dump information about the route
     */
    function dump(): string
    {
        $dump = '';

        if ($this->methodMap === null) {
            $dump .= 'ANY';
        } elseif ($this->methodMap === []) {
            $dump .= 'NONE';
        } else {
            $dump .= implode('|', array_keys($this->methodMap));
        }

        $dump .= ' ';

        if ($this->scheme !== null) {
            $dump .= $this->scheme . '://';
        }

        if ($this->hostPattern !== null) {
            $dump .= $this->hostPattern->dump();
        } elseif ($this->scheme !== null || $this->port !== null) {
            $dump .= '*';
        }

        if ($this->port !== null) {
            $dump .= ':' . $this->port;
        }

        $dump .= $this->pathPattern->dump();

        return $dump;
    }
}
