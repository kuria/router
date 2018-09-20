<?php declare(strict_types=1);

namespace Kuria\Router\Match;

use Kuria\DevMeta\Test;
use Kuria\Router\Result\MethodNotAllowed;

class MethodNotAllowedTest extends Test
{
    function testShouldCreateResult()
    {
        $allowedMethods = ['POST', 'PATCH'];

        $result = new MethodNotAllowed($allowedMethods);

        $this->assertSame($allowedMethods, $result->getAllowedMethods());
    }
}
