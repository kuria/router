<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use PHPUnit\Framework\TestCase;

class RouteBuilderTest extends TestCase
{
    /** @var Pattern */
    private $hostPatternMock;

    /** @var PathPattern */
    private $pathPatternMock;

    /** @var RouteBuilder */
    private $builder;

    protected function setUp()
    {
        $this->hostPatternMock = $this->createMock(Pattern::class);
        $this->pathPatternMock = $this->createMock(PathPattern::class);

        $compilerMock = $this->createMock(PatternCompiler::class);

        $compilerMock->expects($this->any())
            ->method('compilePattern')
            ->with(
                $this->callback(function ($hostPattern) {
                    $this->assertSame($this->builder->getHost(), $hostPattern);

                    return true;
                }),
                $this->callback(function ($requirements) {
                    $this->assertSame($this->builder->getRequirements(), $requirements);

                    return true;
                })
            )
            ->willReturn($this->hostPatternMock);

        $compilerMock->expects($this->any())
            ->method('compilePathPattern')
            ->with(
                $this->callback(function ($hostPattern) {
                    $this->assertSame($this->builder->getPath(), $hostPattern);

                    return true;
                }),
                $this->callback(function ($requirements) {
                    $this->assertSame($this->builder->getRequirements(), $requirements);

                    return true;
                })
            )
            ->willReturn($this->pathPatternMock);

        $this->builder = new RouteBuilder($compilerMock, 'foo_bar');
    }

    function testShouldCopyWithNewName()
    {
        $this->builder
            ->allowedMethods(['GET', 'POST'])
            ->host('server-{serverId}')
            ->port(8080)
            ->path('/page/{name}')
            ->defaults(['serverId' => '1'])
            ->attributes(['controller' => 'PageController'])
            ->requirements(['serverId' => '\d+']);

        $copiedBuilder = $this->builder->copy('baz_qux');

        $this->assertNotSame($this->builder, $copiedBuilder);
        $this->assertSame('baz_qux', $copiedBuilder->getName());
        $this->assertSame(['GET', 'POST'], $this->builder->getAllowedMethods());
        $this->assertSame('server-{serverId}', $this->builder->getHost());
        $this->assertSame(8080, $this->builder->getPort());
        $this->assertSame('/page/{name}', $this->builder->getPath());
        $this->assertSame(['serverId' => '1'], $this->builder->getDefaults());
        $this->assertSame(['controller' => 'PageController'], $this->builder->getAttributes());
        $this->assertSame(['serverId' => '\d+'], $this->builder->getRequirements());
    }

    function testShouldBuildRouteWithAllAttributes()
    {
        $this->builder
            ->allowedMethods(['GET', 'POST'])
            ->host('server-{serverId}')
            ->port(8080)
            ->path('/page/{name}')
            ->defaults(['serverId' => '1'])
            ->attributes(['controller' => 'PageController'])
            ->requirements(['serverId' => '\d+']);

        $this->assertSame(['GET', 'POST'], $this->builder->getAllowedMethods());
        $this->assertSame('server-{serverId}', $this->builder->getHost());
        $this->assertSame(8080, $this->builder->getPort());
        $this->assertSame('/page/{name}', $this->builder->getPath());
        $this->assertSame(['serverId' => '1'], $this->builder->getDefaults());
        $this->assertSame(['controller' => 'PageController'], $this->builder->getAttributes());
        $this->assertSame(['serverId' => '\d+'], $this->builder->getRequirements());

        $route = $this->builder->build();

        $this->assertSame('foo_bar', $route->getName());
        $this->assertSame(['GET', 'POST'], $route->getAllowedMethods());
        $this->assertSame($this->hostPatternMock, $route->getHostPattern());
        $this->assertSame(8080, $route->getPort());
        $this->assertSame($this->pathPatternMock, $route->getPathPattern());
        $this->assertSame(['serverId' => '1'], $route->getDefaults());
        $this->assertSame(['controller' => 'PageController'], $route->getAttributes());
    }

    function testShouldBuildRouteWithEmptyAttributes()
    {
        $this->builder
            ->allowedMethods(null)
            ->host(null)
            ->port(null)
            ->path('')
            ->defaults([])
            ->attributes([])
            ->requirements([]);

        $this->assertNull($this->builder->getAllowedMethods());
        $this->assertNull($this->builder->getHost());
        $this->assertNull($this->builder->getPort());
        $this->assertSame('', $this->builder->getPath());
        $this->assertSame([], $this->builder->getDefaults());
        $this->assertSame([], $this->builder->getAttributes());
        $this->assertSame([], $this->builder->getRequirements());

        $route = $this->builder->build();

        $this->assertSame('foo_bar', $route->getName());
        $this->assertNull($route->getAllowedMethods());
        $this->assertNull($route->getHostPattern());
        $this->assertNull($route->getPort());
        $this->assertSame($this->pathPatternMock, $route->getPathPattern());
        $this->assertSame([], $route->getDefaults());
        $this->assertSame([], $route->getAttributes());
    }

    function testShouldBuildRouteWithMinimalAttributes()
    {
        $this->builder->path('/');

        $this->assertNull($this->builder->getAllowedMethods());
        $this->assertNull($this->builder->getHost());
        $this->assertNull($this->builder->getPort());
        $this->assertSame('/', $this->builder->getPath());
        $this->assertSame([], $this->builder->getDefaults());
        $this->assertSame([], $this->builder->getAttributes());
        $this->assertSame([], $this->builder->getRequirements());

        $route = $this->builder->build();

        $this->assertSame('foo_bar', $route->getName());
        $this->assertNull($route->getAllowedMethods());
        $this->assertNull($route->getHostPattern());
        $this->assertNull($route->getPort());
        $this->assertSame($this->pathPatternMock, $route->getPathPattern());
        $this->assertSame([], $route->getDefaults());
        $this->assertSame([], $route->getAttributes());
    }

    function testShouldModifyPatterns()
    {
        $this->builder->path('path')->host('host');

        $this->builder->prependHost('prefix-');
        $this->assertSame('prefix-host', $this->builder->getHost());

        $this->builder->appendHost('-suffix');
        $this->assertSame('prefix-host-suffix', $this->builder->getHost());

        $this->builder->prependPath('prefix-');
        $this->assertSame('prefix-path', $this->builder->getPath());

        $this->builder->appendPath('-suffix');
        $this->assertSame('prefix-path-suffix', $this->builder->getPath());
    }

    function testShouldModifyNullPatterns()
    {
        $this->builder->prependHost('prefix-');
        $this->assertSame('prefix-', $this->builder->getHost());

        $this->builder->appendHost('-suffix');
        $this->assertSame('prefix--suffix', $this->builder->getHost());

        $this->builder->prependPath('prefix-');
        $this->assertSame('prefix-', $this->builder->getPath());

        $this->builder->appendPath('-suffix');
        $this->assertSame('prefix--suffix', $this->builder->getPath());
    }

    function testShouldThrowExceptionIfInvalidAllowedMethodIsSpecified()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Invalid method "put", valid methods are: GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE, PATCH');

        $this->builder->allowedMethods(['POST', 'put']);
    }

    function testShouldThrowExceptionIfPathIsNotSpecified()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Path pattern must be specified');

        $this->builder->build();
    }
}
