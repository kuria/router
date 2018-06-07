<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\Router\Result\Match;
use Kuria\Router\Route\RouteInterface;
use PHPUnit\Framework\TestCase;

class MatchTest extends TestCase
{
    function testShouldCreateResult()
    {
        $route = $this->createMock(RouteInterface::class);
        $params = ['foo' => 'bar'];

        $result = new Match($route, $params);

        $this->assertSame($route, $result->getRoute());
        $this->assertSame($params, $result->getParameters());
    }
}
