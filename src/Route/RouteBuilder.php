<?php declare(strict_types=1);

namespace Kuria\Router\Route;

class RouteBuilder
{
    protected const VALID_METHOD_MAP = [
        'GET' => true,
        'HEAD' => true,
        'POST' => true,
        'PUT' => true,
        'DELETE' => true,
        'CONNECT' => true,
        'OPTIONS' => true,
        'TRACE' => true,
        'PATCH' => true,
    ];

    /** @var PatternCompiler */
    private $patternCompiler;

    /** @var string */
    private $name;

    /** @var string[]|null */
    private $allowedMethods;

    /** @var string|null */
    private $allowedScheme;

    /** @var string|null */
    private $hostPattern;

    /** @var int|null */
    private $port;

    /** @var string|null */
    private $pathPattern;

    /** @var array */
    private $defaults = [];

    /** @var array */
    private $attributes = [];

    /** @var array */
    private $requirements = [];

    function __construct(PatternCompiler $patternCompiler, string $name)
    {
        $this->patternCompiler = $patternCompiler;
        $this->name = $name;
    }

    /**
     * Copy the builder, specifying a new name
     *
     * @return static
     */
    function copy(string $newName): self
    {
        $clone = clone $this;
        $clone->name = $newName;

        return $clone;
    }

    /**
     * Get configured route name
     *
     * @return string
     */
    function getName(): string
    {
        return $this->name;
    }

    /**
     * Get configured request methods
     *
     * @return string[]|null
     */
    function getAllowedMethods(): ?array
    {
        return $this->allowedMethods;
    }

    /**
     * Match request methods
     *
     * @param string[]|null $allowedMethods
     * @throws \OutOfBoundsException if any of the specified methods is not valid
     */
    function allowedMethods(?array $allowedMethods)
    {
        if ($allowedMethods) {
            foreach ($allowedMethods as $method) {
                if (!isset(static::VALID_METHOD_MAP[$method])) {
                    throw new \OutOfBoundsException(sprintf(
                        'Invalid method "%s", valid methods are: %s',
                        $method,
                        implode(', ', array_keys(static::VALID_METHOD_MAP))
                    ));
                }
            }
        }

        $this->allowedMethods = $allowedMethods;

        return $this;
    }

    /**
     * Get configured host name pattern
     */
    function getHost(): ?string
    {
        return $this->hostPattern;
    }

    /**
     * Match host name pattern
     *
     * @return $this
     */
    function host(?string $hostPattern): self
    {
        $this->hostPattern = $hostPattern;

        return $this;
    }

    /**
     * Add a prefix to the host name pattern
     */
    function prependHost(string $hostPatternPrefix): self
    {
        $this->hostPattern = $hostPatternPrefix . $this->hostPattern;

        return $this;
    }

    /**
     * Add a suffix to the host name pattern
     */
    function appendHost(string $hostPatternSuffix): self
    {
        $this->hostPattern .= $hostPatternSuffix;

        return $this;
    }

    /**
     * Get configured port
     */
    function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * Match port
     *
     * @return $this
     */
    function port(?int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get configured path pattern
     */
    function getPath(): ?string
    {
        return $this->pathPattern;
    }

    /**
     * Match path pattern
     *
     * @return $this
     */
    function path(string $pathPattern): self
    {
        $this->pathPattern = $pathPattern;

        return $this;
    }

    /**
     * Add a prefix to the path pattern
     */
    function prependPath(string $pathPatternPrefix): self
    {
        $this->pathPattern = $pathPatternPrefix . $this->pathPattern;

        return $this;
    }

    /**
     * Add a suffix to the path pattern
     */
    function appendPath(string $pathPatternSuffix): self
    {
        $this->pathPattern .= $pathPatternSuffix;

        return $this;
    }

    /**
     * Get configured defaults
     */
    function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Specify default parameters
     *
     * @return $this
     */
    function defaults(array $defaults): self
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Get configured attributes
     */
    function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Specify arbitrary route attributes
     *
     * @return $this
     */
    function attributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get configured requirements
     */
    function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Specify parameter requirements
     *
     * @see PatternCompiler::compile()
     *
     * @return $this
     */
    function requirements(array $requirements): self
    {
        $this->requirements = $requirements;

        return $this;
    }

    /**
     * Build the route
     *
     * @throws \LogicException if the builder is not configured correctly
     */
    function build(): Route
    {
        if ($this->pathPattern === null) {
            throw new \LogicException('Path pattern must be specified');
        }

        return new Route(
            $this->name,
            $this->allowedMethods,
            $this->allowedScheme,
            $this->hostPattern !== null
                ? $this->patternCompiler->compilePattern($this->hostPattern, $this->requirements)
                : null,
            $this->port,
            $this->patternCompiler->compilePathPattern($this->pathPattern, $this->requirements),
            $this->defaults,
            $this->attributes
        );
    }
}
