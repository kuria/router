<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\DevMeta\Test;
use Kuria\Router\Context;
use Kuria\Router\Subject;
use Kuria\Url\Url;

/**
 * @covers \Kuria\Router\Route\Route
 */
class RouteTest extends Test
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
        $this->assertSame(['GET', 'POST'], $route->getMethods());
        $this->assertSame('https', $route->getScheme());
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

    function provideRoutesToMatch()
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
                $this->route(['methods' => null]),
                $this->subject(['method' => 'PUT']),
                false,
                $successfulMatch,
            ],

            'any scheme' => [
                $this->route(['scheme' => null]),
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
    function testShouldGenerate(Route $route, Context $context, array $parameters, Url $expectedUrl)
    {
        $this->assertLooselyIdentical($expectedUrl, $route->generate($context, $parameters));
    }

    function provideRoutesForGeneration()
    {
        $context = $this->context();
        $compiler = new PatternCompiler();

        return [
            // route, context, parameters, expectedUrl
            'override all' => [
                $this->route(['scheme' => 'http', 'port' => 9090]),
                $context,
                ['serverId' => '99', 'name' => 'about'],
                Url::parse('http://server-99:9090/app/page/about?default=foo'),
            ],

            'override scheme' => [
                $this->route(['scheme' => 'http']),
                $context,
                ['serverId' => '1', 'name' => 'about'],
                Url::parse('http://server-1:8080/app/page/about?default=foo'),
            ],

            'override host' => [
                $this->route(),
                $context,
                ['serverId' => '99', 'name' => 'about'],
                Url::parse('https://server-99:8080/app/page/about?default=foo'),
            ],

            'override port' => [
                $this->route(['port' => 9090]),
                $context,
                ['serverId' => '1', 'name' => 'about'],
                Url::parse('https://server-1:9090/app/page/about?default=foo'),
            ],

            'without scheme' => [
                $this->route(['scheme' => null]),
                $context,
                ['serverId' => '1', 'name' => 'about'],
                Url::parse('https://server-1:8080/app/page/about?default=foo', Url::RELATIVE),
            ],

            'without host' => [
                $this->route(['hostPattern' => null]),
                $context,
                ['name' => 'about'],
                Url::parse('https://server-1:8080/app/page/about?default=foo', Url::RELATIVE),
            ],

            'without port' => [
                $this->route(['port' => null]),
                $context,
                ['serverId' => '1', 'name' => 'about'],
                Url::parse('https://server-1:8080/app/page/about?default=foo', Url::RELATIVE),
            ],

            'without extra params' => [
                $this->route(['defaults' => []]),
                $context,
                ['serverId' => '1', 'name' => 'about'],
                Url::parse('https://server-1:8080/app/page/about', Url::RELATIVE),
            ],

            'plain route' => [
                $this->route([
                    'methods' => null,
                    'scheme' => null,
                    'hostPattern' => null,
                    'port' => null,
                    'pathPattern' => $compiler->compilePathPattern('/foo'),
                    'defaults' => [],
                ]),
                $context,
                [],
                Url::parse('https://server-1:8080/app/foo', Url::RELATIVE),
            ],

            'empty route' => [
                $this->route([
                    'methods' => null,
                    'scheme' => null,
                    'hostPattern' => null,
                    'port' => null,
                    'pathPattern' => $compiler->compilePathPattern(''),
                    'defaults' => [],
                ]),
                $context,
                [],
                Url::parse('https://server-1:8080/app', Url::RELATIVE),
            ],
        ];
    }

    function testShouldThrowExceptionWhenGeneratingWithInvalidHostParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "serverId" must match "{\d+$}ADu"');

        $this->route()->generate($this->context(), ['serverId' => 'not-a-number', 'name' => 'about']);
    }

    function testShouldThrowExceptionWhenGeneratingWithInvalidPathParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "name" must match "{\w+$}ADu"');

        $this->route()->generate($this->context(), ['serverId' => '123', 'name' => '@ foo @']);
    }

    function testShouldGenerateWithInvalidParameterIfCheckingIsDisabled()
    {
        $this->assertLooselyIdentical(
            Url::parse('https://server-not-a-number:8080/app/page/@%20foo%20@?default=foo'),
            $this->route()->generate($this->context(), ['serverId' => 'not-a-number', 'name' => '@ foo @'], false)
        );
    }

    /**
     * @dataProvider provideRoutesToDump
     */
    function testShouldDump(Route $route, string $expectedDump)
    {
        $this->assertSame($expectedDump, $route->dump());
    }

    function provideRoutesToDump()
    {
        return [
            // route, expectedDump
            'full' => [
                $this->route(),
                'GET|POST https://server-{serverId}:8080/page/{name}',
            ],

            'no methods' => [
                $this->route(['methods' => []]),
                'NONE https://server-{serverId}:8080/page/{name}',
            ],

            'no scheme' => [
                $this->route(['scheme' => null]),
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
                $this->route(['methods' => ['HEAD'], 'scheme' => null, 'hostPattern' => null, 'port' => null]),
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
                'methods' => ['GET', 'POST'],
                'scheme' => 'https',
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
        ];

        return new Subject(...array_values(array_merge($defaults, $attrs)));
    }

    private function context(array $attrs = []): Context
    {
        static $defaults = [
            'scheme' => 'https',
            'host' => 'server-1',
            'port' => 8080,
            'basePath' => '/app',
            'httpPort' => null,
            'httpsPort' => null,
        ];

        return new Context(...array_values(array_merge($defaults, $attrs)));
    }
}
