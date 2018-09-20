<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\DevMeta\Test;
use Kuria\Router\Result\Match;
use Kuria\Router\Route\RouteInterface;

class MatchTest extends Test
{
    function testShouldCreateResult()
    {
        $routeMock = $this->createMock(RouteInterface::class);
        $params = ['foo' => 'bar'];

        $result = new Match($routeMock, $params);

        $this->assertSame($routeMock, $result->getRoute());
        $this->assertSame($params, $result->getParameters());
    }
}
