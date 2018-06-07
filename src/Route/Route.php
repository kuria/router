<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\Router\Subject;
use Kuria\Url\Url;

class Route implements RouteInterface
{
    /** @var string */
    private $name;
    /** @var array|null */
    private $allowedMethodMap;
    /** @var string|null */
    private $allowedScheme;
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
        ?array $allowedMethods,
        ?string $allowedScheme,
        ?Pattern $hostPattern,
        ?int $port,
        PathPattern $pathPattern,
        array $defaults = [],
        array $attributes = []
    ) {
        $this->name = $name;
        $this->allowedMethodMap = $allowedMethods !== null ? array_flip($allowedMethods) : null;
        $this->allowedScheme = $allowedScheme;
        $this->hostPattern = $hostPattern;
        $this->port = $port;
        $this->pathPattern = $pathPattern;
        $this->defaults = $defaults;
        $this->attributes = $attributes;

        $this->knownParamMap = array_flip($pathPattern->getParameters());;

        if ($hostPattern) {
            $this->knownParamMap += array_flip($hostPattern->getParameters());
        }
    }

    function getName(): string
    {
        return $this->name;
    }

    function getAllowedMethods(): ?array
    {
        return $this->allowedMethodMap !== null ? array_keys($this->allowedMethodMap) : null;
    }

    function getAllowedScheme(): ?string
    {
        return $this->allowedScheme;
    }

    function getHostPattern(): ?Pattern
    {
        return $this->hostPattern;
    }

    function getPort(): ?int
    {
        return $this->port;
    }

    function getPathPattern(): Pattern
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

    function match(Subject $subject, bool $ignoreMethod = false): ?array
    {
        if (
            // method
            !$ignoreMethod && $this->allowedMethodMap !== null && !isset($this->allowedMethodMap[$subject->method])
            // scheme
            || $this->allowedScheme !== null && $subject->scheme !== $this->allowedScheme
            // host
            || $this->hostPattern !== null && ($subject->host === null || ($hostAttrs = $this->hostPattern->match($subject->host)) === null)
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

    function generate(Url $baseUrl, array $parameters = [], bool $checkRequirements = true): Url
    {
        $parameters += $this->defaults;

        $url = clone $baseUrl;

        if ($this->allowedScheme !== null) {
            $url->setScheme($this->allowedScheme);
        }

        if ($this->hostPattern !== null) {
            $url->setHost($this->hostPattern->generate($parameters, $checkRequirements));
        }

        if ($this->port !== null) {
            $url->setPort($this->port);
        }

        $url->setPath($url->getPath() . $this->pathPattern->generate($parameters, $checkRequirements));

        $url->setQuery(array_diff_key($parameters, $this->knownParamMap)); // set extra params as query

        return $url;
    }

    function dump(): string
    {
        $dump = '';

        if ($this->allowedMethodMap === null) {
            $dump .= 'ANY';
        } elseif ($this->allowedMethodMap === []) {
            $dump .= 'NONE';
        } else {
            $dump .= implode('|', array_keys($this->allowedMethodMap));
        }

        $dump .= ' ';

        if ($this->allowedScheme !== null) {
            $dump .= $this->allowedScheme . '://';
        }

        if ($this->hostPattern !== null) {
            $dump .= $this->hostPattern->dump();
        } elseif ($this->allowedScheme !== null || $this->port !== null) {
            $dump .= '*';
        }

        if ($this->port !== null) {
            $dump .= ':' . $this->port;
        }

        $dump .= $this->pathPattern->dump();

        return $dump;
    }
}
