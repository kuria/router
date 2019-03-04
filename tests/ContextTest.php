<?php declare(strict_types=1);

namespace Kuria\Router;

use Kuria\DevMeta\Test;

/**
 * @covers \Kuria\Router\Context
 */
class ContextTest extends Test
{
    /**
     * @dataProvider provideContextArguments
     */
    function testShouldCreateContext(
        string $scheme,
        int $port,
        ?int $httpPort,
        ?int $httpsPort,
        int $expectedHttpPort,
        int $expectedHttpsPort
    ) {
        $context = new Context(
            $scheme,
            'example.com',
            $port,
            '/app',
            $httpPort,
            $httpsPort
        );

        $this->assertSame($scheme, $context->scheme);
        $this->assertSame('example.com', $context->host);
        $this->assertSame('/app', $context->basePath);
        $this->assertSame($expectedHttpPort, $context->getPortForScheme('http'));
        $this->assertSame($expectedHttpPort, $context->getPortForScheme('__fallback__'));
        $this->assertSame($expectedHttpsPort, $context->getPortForScheme('https'));
    }

    function provideContextArguments()
    {
        return [
            'default http' => [
                'scheme' => 'http',
                'port' => 80,
                'httpPort' => null,
                'httpsPort' => null,
                'expectedHttpPort' => 80,
                'expectedHttpsPort' => 443,
            ],

            'default https' => [
                'scheme' => 'https',
                'port' => 443,
                'httpPort' => null,
                'httpsPort' => null,
                'expectedHttpPort' => 80,
                'expectedHttpsPort' => 443,
            ],

            'non-standard http' => [
                'scheme' => 'http',
                'port' => 8080,
                'httpPort' => null,
                'httpsPort' => null,
                'expectedHttpPort' => 8080,
                'expectedHttpsPort' => 443,
            ],

            'non-standard https' => [
                'scheme' => 'https',
                'port' => 9090,
                'httpPort' => null,
                'httpsPort' => null,
                'expectedHttpPort' => 80,
                'expectedHttpsPort' => 9090,
            ],

            'specific ports (http)' => [
                'scheme' => 'http',
                'port' => 80,
                'httpPort' => 8080,
                'httpsPort' => 9090,
                'expectedHttpPort' => 8080,
                'expectedHttpsPort' => 9090,
            ],

            'specific ports (https)' => [
                'scheme' => 'https',
                'port' => 443,
                'httpPort' => 8080,
                'httpsPort' => 9090,
                'expectedHttpPort' => 8080,
                'expectedHttpsPort' => 9090,
            ],
        ];
    }
}
