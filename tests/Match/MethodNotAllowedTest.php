<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\Router\Result\MethodNotAllowed;
use PHPUnit\Framework\TestCase;

class MethodNotAllowedTest extends TestCase
{
    function testShouldCreateResult()
    {
        $allowedMethods = ['POST', 'PATCH'];

        $result = new MethodNotAllowed($allowedMethods);

        $this->assertSame($allowedMethods, $result->getAllowedMethods());
    }
}
