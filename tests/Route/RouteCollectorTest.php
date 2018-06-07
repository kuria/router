<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use PHPUnit\Framework\TestCase;

class RouteCollectorTest extends TestCase
{
    /** @var RouteCollector */
    private $collector;

    protected function setUp()
    {
        $this->collector = new RouteCollector();
    }

    /**
     * @dataProvider provideRouteBuilderFactoryMethods
     */
    function testShouldCreateRouteBuilder(string $method, ?array $expectedAllowedMethods)
    {
        /** @var RouteBuilder $builder */
        $builder = $this->collector->{$method}('foo_bar');

        $this->assertSame('foo_bar', $builder->getName());
        $this->assertSame($expectedAllowedMethods, $builder->getAllowedMethods());
        $this->assertTrue($this->collector->hasBuilder('foo_bar'));
        $this->assertSame($builder, $this->collector->getBuilder('foo_bar'));
        $this->assertSame(['foo_bar' => $builder], $this->collector->getBuilders());

        $this->collector->removeBuilder('foo_bar');

        $this->assertFalse($this->collector->hasBuilder('foo_bar'));
        $this->assertEmpty($this->collector->getBuilders());
    }

    function provideRouteBuilderFactoryMethods(): array
    {
        return [
            // method, expectedAllowedMethods
            ['add', null],
            ['get', ['GET']],
            ['head', ['HEAD']],
            ['post', ['POST']],
            ['put', ['PUT']],
            ['delete', ['DELETE']],
            ['options', ['OPTIONS']],
            ['patch', ['PATCH']],
        ];
    }

    function testShouldAddVariant()
    {
        $builder = $this->collector
            ->get('foo')
            ->path('/foo')
            ->attributes(['handler' => 'get_foo']);

        $variantBuilder = $this->collector
            ->addVariant('foo', 'foo_submit')
            ->allowedMethods(['POST'])
            ->attributes(['handler' => 'submit_foo']);

        $this->assertNotSame($builder, $variantBuilder);
        $this->assertTrue($this->collector->hasBuilder('foo'));
        $this->assertSame('GET /foo', $this->collector->getBuilder('foo')->build()->dump());
        $this->assertSame(['handler' => 'get_foo'], $this->collector->getBuilder('foo')->getAttributes());
        $this->assertTrue($this->collector->hasBuilder('foo_submit'));
        $this->assertSame('POST /foo', $this->collector->getBuilder('foo_submit')->build()->dump());
        $this->assertSame(['handler' => 'submit_foo'], $this->collector->getBuilder('foo_submit')->getAttributes());
    }

    function testShouldAddGroup()
    {
        $this->collector->addGroup('group_', '/group', function (RouteCollector $c) {
            $this->assertNotSame($this->collector, $c);

            $c->get('foo')->path('/foo');
            $c->post('bar')->path('/bar');
        });

        $builders = $this->collector->getBuilders();

        $this->assertCount(2, $builders);
        $this->assertArrayHasKey('group_foo', $builders);
        $this->assertSame('group_foo', $builders['group_foo']->getName());
        $this->assertSame('/group/foo', $builders['group_foo']->getPath());
        $this->assertArrayHasKey('group_bar', $builders);
        $this->assertSame('group_bar', $builders['group_bar']->getName());
        $this->assertSame('/group/bar', $builders['group_bar']->getPath());
    }

    function testShouldGetRoutes()
    {
        $this->collector->get('page')->path('/page/{name}');
        $this->collector->post('login')->path('/login');

        $routes = $this->collector->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('page', $routes);
        $this->assertSame('page', $routes['page']->getName());
        $this->assertSame('GET /page/{name}', $routes['page']->dump());
        $this->assertArrayHasKey('login', $routes);
        $this->assertSame('login', $routes['login']->getName());
        $this->assertSame('POST /login', $routes['login']->dump());
    }

    function testShouldThrowExceptionIfRouteCannotBeBuilt()
    {
        $this->collector->add('bad');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed when building route "bad" - Path pattern must be specified');

        $this->collector->getRoutes();
    }

    function testShouldThrowExceptionWhenGettingBuilderForUndefinedRoute()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Route "nonexistent" is not defined');

        $this->collector->getBuilder('nonexistent');
    }

    function testShouldClear()
    {
        $this->collector->get('foo')->path('/foo');

        $this->collector->clear();

        $this->assertSame([], $this->collector->getBuilders());
        $this->assertSame([], $this->collector->getRoutes());
    }
}
