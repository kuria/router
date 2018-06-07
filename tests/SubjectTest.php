<?php declare(strict_types=1);

namespace Kuria\Router;

use PHPUnit\Framework\TestCase;

class SubjectTest extends TestCase
{
    function testShouldCreateSubject()
    {
        $subject = new Subject(
            'GET',
            'https',
            'example.com',
            8080,
            '/',
            ['foo' => 'bar']
        );

        $this->assertSame('GET', $subject->method);
        $this->assertSame('https', $subject->scheme);
        $this->assertSame('example.com', $subject->host);
        $this->assertSame(8080, $subject->port);
        $this->assertSame('/', $subject->path);
        $this->assertSame(['foo' => 'bar'], $subject->query);
    }
}
