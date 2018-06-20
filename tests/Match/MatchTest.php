<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\Router\Result\Match;
use Kuria\Router\Route\RouteInterface;
use PHPUnit\Framework\TestCase;

class MatchTest extends TestCase
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
