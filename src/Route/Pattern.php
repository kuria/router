<?php declare(strict_types=1);

namespace Kuria\Router\Route;

class Pattern
{
    protected const PLACEHOLDER_FORMAT = '{%s}';

    /** @var string|null */
    private $prefix;

    /** @var int */
    private $prefixLength;

    /** @var string|null */
    private $regex;

    /** @var array */
    private $parts;

    /** @var string[] part index => name */
    private $parameterMap;

    /** @var array */
    private $requirements;

    function __construct(
        ?string $prefix,
        ?string $regex,
        array $parts,
        array $parameterMap,
        array $requirements
    ) {
        $this->prefix = $prefix;
        $this->prefixLength = $prefix !== null ? strlen($prefix) : 0;
        $this->regex = $regex;
        $this->parts = $parts;
        $this->parameterMap = $parameterMap;
        $this->requirements = $requirements;
    }

    /**
     * Attempt to match the subject
     *
     * Returns all named matches or NULL on failure.
     */
    function match(string $subject): ?array
    {
        // match subject with prefix if there is no regex
        if ($this->regex === null) {
            return $subject === $this->prefix ? [] : null;
        }

        // match prefix and regex
        if (
            $this->prefix !== null && strncmp($this->prefix, $subject, $this->prefixLength) !== 0
            || !preg_match($this->regex, $subject, $match, 0, $this->prefixLength)
        ) {
            return null;
        }

        // success
        $result = [];
        $matchOffset = 0;

        foreach ($this->parameterMap as $param) {
            $result[$param] = $match[++$matchOffset];
        }

        return $result;
    }

    /**
     * Generate a string using the pattern and parameters
     *
     * The $parameters will be checked against existing requirements unless $checkRequirements is FALSE.
     *
     * @throws \InvalidArgumentException if some parameters are missing or invalid
     */
    function generate(array $parameters = [], bool $checkRequirements = true): string
    {
        $result = $this->encodeGeneratedPart((string) $this->prefix);

        foreach ($this->parts as $index => $part) {
            if ($part === null) {
                $param = $this->parameterMap[$index];

                if (!isset($parameters[$param])) {
                    throw new \InvalidArgumentException(sprintf('Missing parameter "%s"', $param));
                }

                $paramValue = (string) $parameters[$param];

                if ($checkRequirements && !preg_match($this->requirements[$param], $paramValue)) {
                    throw new \InvalidArgumentException(sprintf('Parameter "%s" must match "%s"', $param, $this->requirements[$param]));
                }

                $result .= $this->encodeGeneratedPart($paramValue);
            } else {
                $result .= $this->encodeGeneratedPart($part);
            }
        }

        return $result;
    }

    /**
     * Dump the pattern back to a string
     */
    function dump(): string
    {
        $result = (string) $this->prefix;

        foreach ($this->parts as $index => $part) {
            if ($part === null) {
                $result .= sprintf(static::PLACEHOLDER_FORMAT, $this->parameterMap[$index]);
            } else {
                $result .= $part;
            }
        }

        return $result;
    }

    /**
     * Get parameter names
     *
     * Notice: The returned array may have non-consecutive integer indexes.
     *
     * @return string[]
     */
    function getParameters(): array
    {
        return $this->parameterMap;
    }

    /**
     * Get parameter value requirements
     *
     * @return string[]
     */
    function getRequirements(): array
    {
        return $this->requirements;
    }

    protected function encodeGeneratedPart(string $part): string
    {
        return $part;
    }
}
