<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\Router\Subject;
use Kuria\Url\Url;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    function testShouldCreateRoute()
    {
        $hostPatternMock = $this->createMock(Pattern::class);
        $pathPatternMock = $this->createMock(PathPattern::class);

        $route = new Route(
            'foo_bar',
            ['GET', 'POST'],
            'https',
            $hostPatternMock,
            123,
            $pathPatternMock,
            ['default' => 'foo'],
            ['attr' => 'bar']
        );

        $this->assertSame('foo_bar', $route->getName());
        $this->assertSame(['GET', 'POST'], $route->getAllowedMethods());
        $this->assertSame('https', $route->getAllowedScheme());
        $this->assertSame($hostPatternMock, $route->getHostPattern());
        $this->assertSame(123, $route->getPort());
        $this->assertSame($pathPatternMock, $route->getPathPattern());
        $this->assertSame(['default' => 'foo'], $route->getDefaults());
        $this->assertSame(['attr' => 'bar'], $route->getAttributes());
    }

    /**
     * @dataProvider provideRoutesToMatch
     */
    function testShouldMatch(Route $route, Subject $subject, bool $ignoreMethod = false, ?array $expectedResult = null)
    {
        $this->assertSame($expectedResult, $route->match($subject, $ignoreMethod));
    }

    function provideRoutesToMatch(): array
    {
        $successfulMatch = [
            'serverId' => '1',
            'name' => 'about',
            'default' => 'foo',
        ];

        return [
            // route, subject, [ignoreMethod], [expectedResult]
            'match all' => [
                $this->route(),
                $this->subject(),
                false,
                $successfulMatch,
            ],

            'match all alt' => [
                $this->route(),
                $this->subject(['method' => 'POST', 'host' => 'server-2', 'path' => '/page/contact']),
                false,
                ['serverId' => '2', 'name' => 'contact', 'default' => 'foo'],
            ],

            'fail method' => [
                $this->route(),
                $this->subject(['method' => 'PUT']),
            ],

            'fail scheme' => [
                $this->route(),
                $this->subject(['scheme' => 'http']),
            ],

            'fail host' => [
                $this->route(),
                $this->subject(['host' => 'bad']),
            ],

            'fail port' => [
                $this->route(),
                $this->subject(['port' => 9090]),
            ],

            'fail path' => [
                $this->route(),
                $this->subject(['path' => '/bad']),
            ],

            'ignored method' => [
                $this->route(),
                $this->subject(['method' => 'PUT']),
                true,
                $successfulMatch,
            ],

            'any method' => [
                $this->route(['allowedMethods' => null]),
                $this->subject(['method' => 'PUT']),
                false,
                $successfulMatch,
            ],

            'any scheme' => [
                $this->route(['allowedScheme' => null]),
                $this->subject(['scheme' => 'http']),
                false,
                $successfulMatch,
            ],

            'any host' => [
                $this->route(['hostPattern' => null]),
                $this->subject(['host' => 'bad']),
                false,
                ['name' => 'about', 'default' => 'foo'],
            ],

            'any port' => [
                $this->route(['port' => null]),
                $this->subject(['port' => 9090]),
                false,
                $successfulMatch,
            ],
        ];
    }

    /**
     * @dataProvider provideRoutesForGeneration
     */
    function testShouldGenerateAndDump(Route $route, Url $baseUrl, array $parameters, string $expectedUrl)
    {
        $this->assertSame($expectedUrl, $route->generate($baseUrl, $parameters)->build());
    }

    function provideRoutesForGeneration(): array
    {
        $baseUrl = Url::parse('http://localhost:9090/public');
        $emptyBaseUrl = new Url();

        $compiler = new PatternCompiler();

        return [
            // route, baseUrl, parameters, expectedUrl
            'override all' => [
                $this->route(),
                $baseUrl,
                ['serverId' => '1', 'name' => 'about'],
                'https://server-1:8080/public/page/about?default=foo',
            ],

            'without scheme' => [
                $this->route(['allowedScheme' => null]),
                $baseUrl,
                ['serverId' => '1', 'name' => 'about'],
                'http://server-1:8080/public/page/about?default=foo',
            ],

            'without host' => [
                $this->route(['hostPattern' => null]),
                $baseUrl,
                ['name' => 'about'],
                'https://localhost:8080/public/page/about?default=foo',
            ],

            'without port' => [
                $this->route(['port' => null]),
                $baseUrl,
                ['serverId' => '1', 'name' => 'about'],
                'https://server-1:9090/public/page/about?default=foo',
            ],

            'without extra params' => [
                $this->route(['defaults' => []]),
                $baseUrl,
                ['serverId' => '1', 'name' => 'about'],
                'https://server-1:8080/public/page/about',
            ],

            'plain route with empty base' => [
                new Route('dummy', null, null, null, null, $compiler->compilePathPattern('/foo')),
                $emptyBaseUrl,
                [],
                '/foo',
            ],

            'empty route with empty base' => [
                new Route('dummy', null, null, null, null, $compiler->compilePathPattern('')),
                $emptyBaseUrl,
                [],
                '',
            ],
        ];
    }

    function testShouldThrowExceptionWhenGeneratingWithInvalidHostParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "serverId" must match "{\d+$}ADu"');

        $this->route()->generate(new Url(), ['serverId' => 'not-a-number', 'name' => 'about']);
    }

    function testShouldThrowExceptionWhenGeneratingWithInvalidPathParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "name" must match "{\w+$}ADu"');

        $this->route()->generate(new Url(), ['serverId' => '123', 'name' => '@ foo @']);
    }

    function testShouldGenerateWithInvalidParameterIfCheckingIsDisabled()
    {
        $this->assertSame(
            'https://server-not-a-number:8080/page/@%20foo%20@?default=foo',
            $this->route()->generate(new Url(), ['serverId' => 'not-a-number', 'name' => '@ foo @'], false)->build()
        );
    }

    /**
     * @dataProvider provideRoutesToDump
     */
    function testShouldDump(Route $route, string $expectedDump)
    {
        $this->assertSame($expectedDump, $route->dump());
    }

    function provideRoutesToDump(): array
    {
        return [
            // route, expectedDump
            'full' => [
                $this->route(),
                'GET|POST https://server-{serverId}:8080/page/{name}',
            ],

            'no methods' => [
                $this->route(['allowedMethods' => []]),
                'NONE https://server-{serverId}:8080/page/{name}',
            ],

            'no scheme' => [
                $this->route(['allowedScheme' => null]),
                'GET|POST server-{serverId}:8080/page/{name}',
            ],

            'no host' => [
                $this->route(['hostPattern' => null]),
                'GET|POST https://*:8080/page/{name}',
            ],

            'no port' => [
                $this->route(['port' => null]),
                'GET|POST https://server-{serverId}/page/{name}',
            ],

            'single method with no scheme, host or port' => [
                $this->route(['allowedMethods' => ['HEAD'], 'allowedScheme' => null, 'hostPattern' => null, 'port' => null]),
                'HEAD /page/{name}',
            ],

            'path only' => [
                new Route('dummy', null, null, null, null, new PathPattern('/', null, [], [], [])),
                'ANY /',
            ],
        ];
    }

    private function route(array $attrs = []): Route
    {
        static $defaults;

        if ($defaults === null) {
            $compiler = new PatternCompiler();

            $defaults = [
                'name' => 'dummy',
                'allowedMethods' => ['GET', 'POST'],
                'allowedScheme' => 'https',
                'hostPattern' => $compiler->compilePattern('server-{serverId}', ['serverId' => '\d+']),
                'port' => 8080,
                'pathPattern' => $compiler->compilePathPattern('/page/{name}', ['name' => '\w+']),
                'defaults' => ['default' => 'foo'],
            ];
        }

        return new Route(...array_values(array_merge($defaults, $attrs)));
    }

    private function subject(array $attrs = []): Subject
    {
        static $defaults = [
            'method' => 'GET',
            'scheme' => 'https',
            'host' => 'server-1',
            'port' => 8080,
            'path' => '/page/about',
            'query' => [],
        ];

        return new Subject(...array_values(array_merge($defaults, $attrs)));
    }
}
