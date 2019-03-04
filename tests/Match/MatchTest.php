<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\DevMeta\Test;
use Kuria\Router\Result\Match;
use Kuria\Router\Route\Route;
use Kuria\Router\Subject;

/**
 * @covers \Kuria\Router\Result\Match
 * @covers \Kuria\Router\Result\Result
 */
class MatchTest extends Test
{
    function testShouldCreateResult()
    {
        $subjectMock = $this->createMock(Subject::class);
        $routeMock = $this->createMock(Route::class);
        $params = ['foo' => 'bar'];

        $result = new Match($subjectMock, $routeMock, $params);

        $this->assertSame($subjectMock, $result->getSubject());
        $this->assertSame($routeMock, $result->getRoute());
        $this->assertSame($params, $result->getParameters());
    }
}
