<?php declare(strict_types=1);

namespace Kuria\Router;

use Kuria\DevMeta\Test;
use Kuria\Router\Result\Match;
use Kuria\Router\Result\MethodNotAllowed;
use Kuria\Router\Result\NotFound;
use Kuria\Router\Result\Result;
use Kuria\Router\Route\PatternCompiler;
use Kuria\Router\Route\Route;
use Kuria\Router\Route\RouteBuilder;
use Kuria\Router\Route\RouteCollector;
use Kuria\Url\Url;

/**
 * @covers \Kuria\Router\Router
 */
class RouterTest extends Test
{
    /** @var Router */
    private $router;

    protected function setUp()
    {
        $this->router = new Router();
    }

    function testShouldConfigureDefaultContext()
    {
        $this->assertNull($this->router->getDefaultContext());

        $context = new Context('https', 'foo', 9090, '');
        $this->router->setDefaultContext($context);

        $this->assertSame($context, $this->router->getDefaultContext());
    }

    function testShouldConfigureRoutes()
    {
        $setRoutes = [
            'foo' => $this->createMock(Route::class),
            'bar' => $this->createMock(Route::class),
            'baz' => $this->createMock(Route::class),
        ];

        $this->router->setRoutes($setRoutes);

        $this->assertSame($setRoutes, $this->router->getRoutes());
        $this->assertTrue($this->router->hasRoute('foo'));
        $this->assertTrue($this->router->hasRoute('bar'));
        $this->assertTrue($this->router->hasRoute('baz'));
        $this->assertFalse($this->router->hasRoute('nonexistent'));

        $addedRoutes = [
            'baz' => $this->createMock(Route::class),
            'qux' => $this->createMock(Route::class),
            'quux' => $this->createMock(Route::class),
        ];

        $this->router->addRoutes($addedRoutes);
        $this->assertSame(array_merge($setRoutes, $addedRoutes), $this->router->getRoutes());

        $this->router->setRoutes([]);
        $this->assertSame([], $this->router->getRoutes());
    }

    function testShouldConfigureRoutesUsingCustomCollector()
    {
        $setRoutes = [
            'foo' => $this->createMock(Route::class),
            'bar' => $this->createMock(Route::class),
            'baz' => $this->createMock(Route::class),
        ];

        $this->router->setRoutes($setRoutes);

        $collectorMock = $this->createMock(RouteCollector::class);

        $collectorMock->expects($this->once())
            ->method('clear');

        $collectedRoutes = [
            'baz' => $this->createMock(Route::class),
            'qux' => $this->createMock(Route::class),
            'quux' => $this->createMock(Route::class),
        ];

        $collectorMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($collectedRoutes);

        $this->router->setRouteCollector($collectorMock);

        $callbackCalled = false;

        $this->router->defineRoutes(function ($collector) use ($collectorMock, &$callbackCalled) {
            $this->assertFalse($callbackCalled);
            $this->assertSame($collectorMock, $collector);
            $callbackCalled = true;
        });

        $this->assertTrue($callbackCalled);
        $this->assertSame(array_merge($setRoutes, $collectedRoutes), $this->router->getRoutes());
    }

    function testShouldDefineRoutes()
    {
        $this->router->defineRoutes(function (RouteCollector $c) {
            $c->get('index')->path('/');
            $c->post('login')->path('/login');
        });

        $routes = $this->router->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('index', $routes);
        $this->assertSame('GET /', $routes['index']->dump());
        $this->assertArrayHasKey('login', $routes);
        $this->assertSame('POST /login', $routes['login']->dump());
    }

    /**
     * @dataProvider provideRoutesToMatch
     */
    function testShouldMatch(array $routes, Subject $subject, Result $expectedResult)
    {
        $this->router->setRoutes($routes);

        $this->assertLooselyIdentical($expectedResult, $this->router->match($subject));
    }

    /**
     * @dataProvider provideRoutesToMatch
     */
    function testShouldMatchPath(array $routes, Subject $subject, Result $expectedResult)
    {
        $this->router->setRoutes($routes);

        $context = new Context($subject->scheme, $subject->host, $subject->port, '/app');

        $this->assertLooselyIdentical(
            $expectedResult,
            $this->router->matchPath($subject->method, $subject->path, $context),
            false,
            'should match path using given context'
        );

        $this->router->setDefaultContext($context);

        $this->assertLooselyIdentical(
            $expectedResult,
            $this->router->matchPath($subject->method, $subject->path),
            false,
            'should match path using default context'
        );
    }

    function provideRoutesToMatch()
    {
        $neverMatchingRouteMock = $this->createConfiguredMock(Route::class, [
            'match' => null,
        ]);

        $alwaysMatchingRouteMock = $this->createConfiguredMock(Route::class, [
            'match' => ['always_match' => 'yes'],
        ]);

        $alwaysMatchingRouteMockB = $this->createConfiguredMock(Route::class, [
            'match' => ['always_match_b' => 'yes'],
        ]);

        $compiler = new PatternCompiler();

        $headRoute = (new RouteBuilder($compiler, 'head_route'))
            ->methods(['HEAD'])
            ->path('/foo')
            ->defaults(['head_route' => 'yes'])
            ->build();

        $getRoute = (new RouteBuilder($compiler, 'get_route'))
            ->methods(['GET'])
            ->path('/foo')
            ->defaults(['get_route' => 'yes'])
            ->build();

        $postPutRoute = (new RouteBuilder($compiler, 'post_put_route'))
            ->methods(['POST', 'PUT'])
            ->path('/foo')
            ->defaults(['post_put_route' => 'yes'])
            ->build();

        $routes = [$headRoute, $getRoute, $postPutRoute];

        return [
            // routes, subject, expectedResult
            'no routes' => [
                [],
                $subject = new Subject('GET', 'http', 'example.com', 80, '/'),
                new NotFound($subject),
            ],

            'no matching routes' => [
                [$neverMatchingRouteMock, $neverMatchingRouteMock],
                $subject = new Subject('GET', 'http', 'example.com', 80, '/'),
                new NotFound($subject),
            ],

            'match routes in order' => [
                [$neverMatchingRouteMock, $alwaysMatchingRouteMock, $alwaysMatchingRouteMockB],
                $subject = new Subject('GET', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $alwaysMatchingRouteMock, ['always_match' => 'yes']),
            ],

            'match routes in different order' => [
                [$neverMatchingRouteMock, $alwaysMatchingRouteMockB, $alwaysMatchingRouteMock],
                $subject = new Subject('GET', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $alwaysMatchingRouteMockB, ['always_match_b' => 'yes']),
            ],

            'method not allowed' => [
                $routes,
                $subject = new Subject('PATCH', 'http', 'example.com', 80, '/foo'),
                new MethodNotAllowed($subject, ['HEAD', 'GET', 'POST', 'PUT']),
            ],

            'no matching method or path' => [
                $routes,
                $subject = new Subject('PATCH', 'http', 'example.com', 80, '/bar'),
                new NotFound($subject),
            ],

            'match HEAD route' => [
                [$getRoute, $headRoute],
                $subject = new Subject('HEAD', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $headRoute, ['head_route' => 'yes']),
            ],

            'match GET route' => [
                $routes,
                $subject = new Subject('GET', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $getRoute, ['get_route' => 'yes']),
            ],

            'match POST route' => [
                $routes,
                $subject = new Subject('POST', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $postPutRoute, ['post_put_route' => 'yes']),
            ],

            'match PUT route' => [
                $routes,
                $subject = new Subject('PUT', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $postPutRoute, ['post_put_route' => 'yes']),
            ],

            'match GET route as fallback from HEAD' => [
                [$neverMatchingRouteMock, $getRoute],
                $subject = new Subject('HEAD', 'http', 'example.com', 80, '/foo'),
                new Match($subject, $getRoute, ['get_route' => 'yes']),
            ],
        ];
    }

    function testShouldThrowExceptionWhenMatchingPathWithNoContext()
    {
        $this->router->setRoutes(['foo' => $this->createMock(Route::class)]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Default context is not defined');

        $this->router->matchPath('GET', '/foo');
    }

    function testShouldGenerate()
    {
        $context = new Context('http', 'example.com', 8080, '/app');
        $generatedUrl = new Url();

        $routeMock = $this->createMock(Route::class);
        $routeMock->expects($this->exactly(2))
            ->method('generate')
            ->with(
                $this->identicalTo($context),
                $this->identicalTo(['a' => 123, 'b' => '456']),
                $this->isTrue()
            )
            ->willReturn($generatedUrl);

        $this->router->setRoutes(['foo' => $routeMock]);

        $this->assertSame(
            $generatedUrl,
            $this->router->generate('foo', ['a' => 123, 'b' => '456'], true, $context),
            'should generate URL using given context'
        );

        $this->router->setDefaultContext($context);

        $this->assertSame(
            $generatedUrl,
            $this->router->generate('foo', ['a' => 123, 'b' => '456']),
            'should generate URL using default context'
        );
    }

    function testShouldGenerateWithoutCheckingRequirements()
    {
        $context = new Context('http', 'example.com', 8080, '/app');
        $generatedUrl = new Url();

        $routeMock = $this->createMock(Route::class);
        $routeMock->expects($this->once())
            ->method('generate')
            ->with(
                $this->identicalTo($context),
                $this->identicalTo(['a' => 123, 'b' => '456']),
                $this->isFalse()
            )
            ->willReturn($generatedUrl);

        $this->router->setRoutes(['foo' => $routeMock]);

        $this->assertSame(
            $generatedUrl,
            $this->router->generate('foo', ['a' => 123, 'b' => '456'], false, $context),
            'should generate URL using default context'
        );
    }

    function testShouldThrowExceptionWhenGeneratingUrlForNonexistentRoute()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('There is no route named "nonexistent"');

        $this->router->generate('nonexistent');
    }

    function testShouldThrowExceptionWhenGeneratingUrlWithNoContext()
    {
        $this->router->setRoutes(['foo' => $this->createMock(Route::class)]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Default context is not defined');

        $this->router->generate('foo', []);
    }
}
