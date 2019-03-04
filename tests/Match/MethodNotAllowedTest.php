<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\DevMeta\Test;
use Kuria\Router\Result\MethodNotAllowed;
use Kuria\Router\Subject;

/**
 * @covers \Kuria\Router\Result\MethodNotAllowed
 * @covers \Kuria\Router\Result\Result
 */
class MethodNotAllowedTest extends Test
{
    function testShouldCreateResult()
    {
        $subjectMock = $this->createMock(Subject::class);
        $allowedMethods = ['POST', 'PATCH'];

        $result = new MethodNotAllowed($subjectMock, $allowedMethods);

        $this->assertSame($subjectMock, $result->getSubject());
        $this->assertSame($allowedMethods, $result->getAllowedMethods());
    }
}
