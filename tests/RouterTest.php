<?php declare(strict_types=1);

namespace Kuria\Router;

use Kuria\Router\Result\Match;
use Kuria\Router\Result\MethodNotAllowed;
use Kuria\Router\Result\NotFound;
use Kuria\Router\Result\ResultInterface;
use Kuria\Router\Route\PatternCompiler;
use Kuria\Router\Route\Route;
use Kuria\Router\Route\RouteBuilder;
use Kuria\Router\Route\RouteCollector;
use Kuria\Url\Url;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected function tearDown()
    {
        Url::clearCurrentUrlCache();
    }

    function testShouldCreateRouterWithDefaultBaseUrl()
    {
        Url::setCurrent(Url::parse('https://user:pwd@example.com:8080/foo?bar=baz#fragment'));

        $router = new Router();

        $this->assertSame('https://example.com:8080', $router->getBaseUrl()->build());
    }

    function testShouldCreateRouterWithCustomBaseUrl()
    {
        $baseUrl = new Url();
        $router = new Router($baseUrl);

        $this->assertSame($baseUrl, $router->getBaseUrl());

        $router->setBaseUrl($newBaseUrl = new Url());
        $this->assertSame($newBaseUrl, $router->getBaseUrl());
    }

    function testShouldConfigureRoutes()
    {
        $router = new Router();

        $setRoutes = [
            'foo' => $this->createMock(Route::class),
            'bar' => $this->createMock(Route::class),
            'baz' => $this->createMock(Route::class),
        ];

        $router->setRoutes($setRoutes);

        $this->assertSame($setRoutes, $router->getRoutes());
        $this->assertTrue($router->hasRoute('foo'));
        $this->assertTrue($router->hasRoute('bar'));
        $this->assertTrue($router->hasRoute('baz'));
        $this->assertFalse($router->hasRoute('nonexistent'));

        $addedRoutes = [
            'baz' => $this->createMock(Route::class),
            'qux' => $this->createMock(Route::class),
            'mlem' => $this->createMock(Route::class),
        ];

        $router->addRoutes($addedRoutes);
        $this->assertSame($addedRoutes + $setRoutes, $router->getRoutes());

        $router->setRoutes([]);
        $this->assertSame([], $router->getRoutes());
    }

    function testShouldConfigureRoutesUsingCustomCollector()
    {
        $router = new Router();

        $setRoutes = [
            'foo' => $this->createMock(Route::class),
            'bar' => $this->createMock(Route::class),
            'baz' => $this->createMock(Route::class),
        ];

        $router->setRoutes($setRoutes);

        $collectorMock = $this->createMock(RouteCollector::class);

        $collectorMock->expects($this->once())
            ->method('clear');

        $collectedRoutes = [
            'baz' => $this->createMock(Route::class),
            'qux' => $this->createMock(Route::class),
            'mlem' => $this->createMock(Route::class),
        ];

        $collectorMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($collectedRoutes);

        $router->setRouteCollector($collectorMock);

        $callbackCalled = false;

        $router->defineRoutes(function ($collector) use ($collectorMock, &$callbackCalled) {
            $this->assertFalse($callbackCalled);
            $this->assertSame($collectorMock, $collector);
            $callbackCalled = true;
        });

        $this->assertTrue($callbackCalled);
        $this->assertSame($collectedRoutes + $setRoutes, $router->getRoutes());
    }

    function testShouldDefineRoutes()
    {
        $router = new Router();

        $router->defineRoutes(function (RouteCollector $c) {
            $c->get('index')->path('/');
            $c->post('login')->path('/login');
        });

        $routes = $router->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('index', $routes);
        $this->assertSame('GET /', $routes['index']->dump());
        $this->assertArrayHasKey('login', $routes);
        $this->assertSame('POST /login', $routes['login']->dump());
    }

    /**
     * @dataProvider provideRequestsToMatch
     */
    function testShouldMatch(array $routes, string $method, Url $url, ResultInterface $expectedResult)
    {
        $router = new Router();
        $router->setRoutes($routes);

        $this->assertEquals($expectedResult, $router->match($method, $url));
    }

    function provideRequestsToMatch(): array
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

        $fooHeadRequestRoute = (new RouteBuilder($compiler, 'foo_head'))
            ->allowedMethods(['HEAD'])
            ->path('/foo')
            ->defaults(['foo_head' => 'yes'])
            ->build();

        $fooGetRequestRoute = (new RouteBuilder($compiler, 'foo_get'))
            ->allowedMethods(['GET'])
            ->path('/foo')
            ->defaults(['foo_get' => 'yes'])
            ->build();

        $fooPostPutRequestRoute = (new RouteBuilder($compiler, 'foo_post_put'))
            ->allowedMethods(['POST', 'PUT'])
            ->path('/foo')
            ->defaults(['foo_post_put' => 'yes'])
            ->build();

        $fooRoutes = [$fooHeadRequestRoute, $fooGetRequestRoute, $fooPostPutRequestRoute];

        $fooUrl = Url::parse('/foo');
        $barUrl = Url::parse('/bar');

        return [
            // routes, method, url, expectedResult
            'no routes' => [
                [],
                'GET',
                $fooUrl,
                new NotFound(),
            ],

            'no matching routes' => [
                [$neverMatchingRouteMock, $neverMatchingRouteMock],
                'GET',
                $fooUrl,
                new NotFound(),
            ],

            'match routes in order' => [
                [$neverMatchingRouteMock, $alwaysMatchingRouteMock, $alwaysMatchingRouteMockB],
                'GET',
                $fooUrl,
                new Match($alwaysMatchingRouteMock, ['always_match' => 'yes']),
            ],

            'match routes in different order' => [
                [$neverMatchingRouteMock, $alwaysMatchingRouteMockB, $alwaysMatchingRouteMock],
                'GET',
                $fooUrl,
                new Match($alwaysMatchingRouteMockB, ['always_match_b' => 'yes']),
            ],

            'method not allowed' => [
                $fooRoutes,
                'PATCH',
                $fooUrl,
                new MethodNotAllowed(['HEAD', 'GET', 'POST', 'PUT']),
            ],

            'no matching method or path' => [
                $fooRoutes,
                'PATCH',
                $barUrl,
                new NotFound(),
            ],

            'match HEAD route' => [
                [$fooGetRequestRoute, $fooHeadRequestRoute],
                'HEAD',
                $fooUrl,
                new Match($fooHeadRequestRoute, ['foo_head' => 'yes']),
            ],

            'match GET route' => [
                $fooRoutes,
                'GET',
                $fooUrl,
                new Match($fooGetRequestRoute, ['foo_get' => 'yes']),
            ],

            'match POST route' => [
                $fooRoutes,
                'POST',
                $fooUrl,
                new Match($fooPostPutRequestRoute, ['foo_post_put' => 'yes']),
            ],

            'match PUT route' => [
                $fooRoutes,
                'PUT',
                $fooUrl,
                new Match($fooPostPutRequestRoute, ['foo_post_put' => 'yes']),
            ],

            'match GET route as fallback from HEAD' => [
                [$neverMatchingRouteMock, $fooGetRequestRoute],
                'HEAD',
                $fooUrl,
                new Match($fooGetRequestRoute, ['foo_get' => 'yes']),
            ],
        ];
    }

    /**
     * @dataProvider provideExpectedSubjects
     */
    function testShouldCreateSubjectForMatching(Url $baseUrl, string $method, Url $url, Subject $expectedSubject)
    {
        $router = new Router($baseUrl);

        $routeMock = $this->createMock(Route::class);

        $routeMock->expects($this->atLeastOnce())
            ->method('match')
            ->with($expectedSubject)
            ->willReturn([]);

        $router->setRoutes(['mock' => $routeMock]);
        $router->match($method, $url);
    }

    function provideExpectedSubjects(): array
    {
        return [
            // baseUrl, method, url, expectedSubject
            'should remove base url path prefix' => [
                Url::parse('https://example.com/public'),
                'GET',
                Url::parse('https://example.com/public/about'),
                new Subject('GET', 'https', 'example.com', null, '/about', []),
            ],

            'should not remove non-matching base url path prefix' => [
                Url::parse('https://example.com/public'),
                'GET',
                Url::parse('https://example.com/foo'),
                new Subject('GET', 'https', 'example.com', null, '/foo', []),
            ],

            'should decode path and query' => [
                new Url(),
                'POST',
                Url::parse('http://localhost:8080/%C5%BElu%C5%A5ou%C4%8Dk%C3%BDk%C5%AF%C5%88%40example?foo=bar%20baz'),
                new Subject('POST', 'http', 'localhost', 8080, '/žluťoučkýkůň@example', ['foo' => 'bar baz']),
            ],
        ];
    }

    function testShouldGenerate()
    {
        $baseUrl = new Url();
        $router = new Router($baseUrl);

        $generatedUrl = new Url();

        $fooRouteMock = $this->createMock(Route::class);
        $fooRouteMock->expects($this->once())
            ->method('generate')
            ->with(
                $this->identicalTo($baseUrl),
                $this->identicalTo(['a' => 123, 'b' => '456']),
                $this->isTrue()
            )
            ->willReturn($generatedUrl);

        $router->setRoutes(['foo' => $fooRouteMock]);

        $this->assertSame($generatedUrl, $router->generate('foo', ['a' => 123, 'b' => '456']));
    }

    function testShouldThrowExceptionWhenGeneratingUrlForNonexistentRoute()
    {
        $router = new Router();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('There is no route named "nonexistent"');

        $router->generate('nonexistent');
    }
}
