<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\DevMeta\Test;

class PatternCompilerTest extends Test
{
    /** @var PatternCompiler */
    private $compiler;

    protected function setUp()
    {
        $this->compiler = new PatternCompiler();
    }

    /**
     * @dataProvider providePatternsToCompile
     */
    function testShouldCompilePattern(array $args, Pattern $expectedPattern)
    {
        $this->assertLooselyIdentical($expectedPattern, $this->compiler->compilePattern(...$args));
    }

    function providePatternsToCompile()
    {
        return [
            // args, expectedPattern
            'empty' => [
                [''],
                new Pattern('', null, [], [], []),
            ],

            'plain placeholder' => [
                ['{foo}'],
                new Pattern(null, '{(.+)$}ADu', [null], [0 => 'foo'], ['foo' => '{.+$}ADu']),
            ],

            'placeholder with prefix and requirement' => [
                ['/page/{id}', ['id' => '\d+']],
                new Pattern('/page/', '{(\d+)$}ADu', [null], [0 => 'id'], ['id' => '{\d+$}ADu']),
            ],

            'placeholder with custom default requirement' => [
                ['bar-{baz}', [], '\d+'],
                new Pattern('bar-', '{(\d+)$}ADu', [null], [0 => 'baz'], ['baz' => '{\d+$}ADu']),
            ],

            'multiple placeholders with complex requirements' => [
                [
                    'db-{database}-{tableId}-{rowId}.{format}.dat',
                    ['database' => '\w+', 'format' => '(json|xml)'],
                    '(\d+|0[xX][0-9a-fA-F]+)',
                ],
                new Pattern(
                    'db-',
                    '{(\w+)\-((?:\d+|0[xX][0-9a-fA-F]+))\-((?:\d+|0[xX][0-9a-fA-F]+))\.((?:json|xml))\.dat$}ADu',
                    [null, '-', null, '-', null, '.', null, '.dat'],
                    [
                        0 => 'database',
                        2 => 'tableId',
                        4 => 'rowId',
                        6 => 'format',
                    ],
                    [
                        'database' => '{\w+$}ADu',
                        'tableId' => '{(\d+|0[xX][0-9a-fA-F]+)$}ADu',
                        'rowId' => '{(\d+|0[xX][0-9a-fA-F]+)$}ADu',
                        'format' => '{(json|xml)$}ADu',
                    ]
                ),
            ],
        ];
    }

    /**
     * @dataProvider providePathPatternsToCompile
     */
    function testShouldCompilePathPattern(array $args, PathPattern $expectedPattern)
    {
        $this->assertLooselyIdentical($expectedPattern, $this->compiler->compilePathPattern(...$args));
    }

    function providePathPatternsToCompile()
    {
        return [
            // args, expectedPattern
            'empty' => [
                [''],
                new PathPattern('', null, [], [], []),
            ],

            'default placeholder with prefix' => [
                ['/page/{name}'],
                new PathPattern('/page/', '{([^/]+)$}ADu', [null], [0 => 'name'], ['name' => '{[^/]+$}ADu']),
            ],

            'multiple placeholders with complex requirements' => [
                [
                    '/api/v1/{database}/{tableId}/{rowId}.{format}/',
                    ['database' => '\w+', 'format' => '(json|xml)'],
                    '(\d+|0[xX][0-9a-fA-F]+)',
                ],
                new PathPattern(
                    '/api/v1/',
                    '{(\w+)/((?:\d+|0[xX][0-9a-fA-F]+))/((?:\d+|0[xX][0-9a-fA-F]+))\.((?:json|xml))/$}ADu',
                    [null, '/', null, '/', null, '.', null, '/'],
                    [
                        0 => 'database',
                        2 => 'tableId',
                        4 => 'rowId',
                        6 => 'format',
                    ],
                    [
                        'database' => '{\w+$}ADu',
                        'tableId' => '{(\d+|0[xX][0-9a-fA-F]+)$}ADu',
                        'rowId' => '{(\d+|0[xX][0-9a-fA-F]+)$}ADu',
                        'format' => '{(json|xml)$}ADu',
                    ]
                ),
            ],
        ];
    }
}
