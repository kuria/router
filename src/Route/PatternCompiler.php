<?php declare(strict_types=1);

namespace Kuria\Router\Route;

/**
 * Pattern compiler
 *
 * Example pattern: /foo/{bar}/{baz}
 *
 * The $requirements map may contain regexp patterns (without delimiters) for each named parameter.
 * Patterns in $requirements are anchored automatically and should not contain ^ or $.
 */
class PatternCompiler
{
    protected const PLACEHOLDER_PATTERN = '{\{([^\}]++)\}}';

    function compilePattern(string $pattern, array $requirements = [], string $defaultRequirement = '.+'): Pattern
    {
        /** @var Pattern $result */
        $result = $this->compile(Pattern::class, $pattern, $requirements, $defaultRequirement);

        return $result;
    }

    function compilePathPattern(string $pattern, array $requirements = [], string $defaultRequirement = '[^/]+'): PathPattern
    {
        /** @var PathPattern $result */
        $result = $this->compile(PathPattern::class, $pattern, $requirements, $defaultRequirement);

        return $result;
    }

    /**
     * @return object
     */
    private function compile(string $className, string $pattern, array $requirements, string $defaultRequirement)
    {
        preg_match_all(self::PLACEHOLDER_PATTERN, $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        if ($matches) {
            $prefix = $matches[0][0][1] > 0 ? substr($pattern, 0, $matches[0][0][1]) : null;
            [$regex, $parts, $parameterMap] = $this->compilePlaceholderMatches($pattern, $matches, $requirements, $defaultRequirement);
            $compiledRequirements = $this->compileRequirements($parameterMap, $requirements, $defaultRequirement);
        } else {
            // pattern without placeholders
            $prefix = $pattern;
            $regex = null;
            $parts = [];
            $parameterMap = [];
            $compiledRequirements = [];
        }

        return new $className($prefix, $regex, $parts, $parameterMap, $compiledRequirements);
    }

    private function compilePlaceholderMatches(string $pattern, array $matches, array $requirements, string $defaultRequirement): array
    {
        $regex = '{';
        $parts = [];
        $parameterMap = [];

        $previousPlaceholderEnd = null;

        $partIndex = -1;

        $defaultRequirement = $this->disableCapturingGroupsInRegex($defaultRequirement);

        foreach ($matches as $index => list(list($match, $offset), list($param))) {
            if ($index > 0 && $offset > $previousPlaceholderEnd) {
                $parts[++$partIndex] = substr($pattern, $previousPlaceholderEnd, $offset - $previousPlaceholderEnd);
                $regex .= preg_quote($parts[$partIndex]);
            }

            $regex .= '(';

            if (isset($requirements[$param])) {
                $regex .= $this->disableCapturingGroupsInRegex($requirements[$param]);
            } else {
                $regex .= $defaultRequirement;
            }

            $regex .= ')';

            $parts[++$partIndex] = null;
            $parameterMap[$partIndex] = $param;

            $previousPlaceholderEnd = $offset + strlen($match);
        }

        if ($previousPlaceholderEnd < strlen($pattern)) {
            $parts[++$partIndex] = substr($pattern, $previousPlaceholderEnd);
            $regex .= preg_quote($parts[$partIndex]);
        }

        $regex .= '$}ADu';

        return [$regex, $parts, $parameterMap];
    }

    private function disableCapturingGroupsInRegex(string $regex): string
    {
        $result = '';
        $escaped = false;

        for ($i = 0, $length = strlen($regex); $i < $length; ++$i) {
            if ($regex[$i] === '\\') {
                // escape
                $escaped = !$escaped;
            } else {
                // other characters
                if (
                    '(' === $regex[$i]
                    && !$escaped
                    && ($next = $i + 1) < $length
                    && $regex[$next] !== '?'
                    && $regex[$next] !== '*'
                ) {
                    // capture group
                    $result .= '(?:';
                    continue;
                }

                // consume previous escape
                $escaped = false;
            }

            $result .= $regex[$i];
        }

        return $result;
    }

    private function compileRequirements(array $parameterMap, array $requirements, string $defaultRequirement): array
    {
        $compiled = [];

        foreach ($parameterMap as $param) {
            $compiled[$param] = '{' . ($requirements[$param] ?? $defaultRequirement) . '$}ADu';
        }

        return $compiled;
    }
}
