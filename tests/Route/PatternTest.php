<?php declare(strict_types=1);

namespace Kuria\Router\Route;

use Kuria\DevMeta\Test;

class PatternTest extends Test
{
    /**
     * @dataProvider providePatterns
     */
    function testPattern(array $patternAttrs, array $matchTestCases, array $generateTestCases, string $expectedDump)
    {
        $pattern = $this->createPattern($patternAttrs);

        // getters
        $this->assertSame($patternAttrs['parameterMap'] ?? [], $pattern->getParameters());
        $this->assertSame($patternAttrs['requirements'] ?? [], $pattern->getRequirements());

        // match
        foreach ($matchTestCases as $subject => $expectedResult) {
            $result = $pattern->match($subject);

            if ($expectedResult === null) {
                $this->assertNull($result, "expected subject \"{$subject}\" to not match");
            } else {
                $this->assertSame($expectedResult, $result, "expected subject \"{$subject}\" to match");
            }
        }

        // generate
        foreach ($generateTestCases as [$params, $expectedResult]) {
            $this->assertSame(
                $expectedResult,
                $pattern->generate($params),
                sprintf('expected generate(%s) to return "%s"', json_encode($params), $expectedResult)
            );
        }

        // dump
        $this->assertSame($expectedDump, $pattern->dump(), "expected dump() to return \"{$expectedDump}\"");
    }

    function providePatterns(): array
    {
        return [
            // patternAttrs, matchTestCases, generateTestCases, expectedDump
            'no prefix and no regex' => [
                'attrs' => [],
                'matchTestCases' => [
                    '' => null,
                    '/foo' => null,
                ],
                'generateTestCases' => [
                    [[], ''],
                ],
                'expectedDump' => '',
            ],

            'empty prefix only' => [
                'attrs' => ['prefix' => ''],
                'matchTestCases' => [
                    '' => [],
                    '/foo' => null,
                ],
                'generateTestCases' => [
                    [[], ''],
                ],
                'expectedDump' => '',
            ],

            'regex only' => [
                'attrs' => [
                    'regex' => '{/foo$}ADu',
                    'parts' => ['/foo'],
                ],
                'matchTestCases' => [
                    '' => null,
                    '/foo' => [],
                    '/foox' => null,
                    '/bar' => null,
                ],
                'generateTestCases' => [
                    [[], '/foo'],
                ],
                'expectedDump' => '/foo',
            ],

            'regex with params' => [
                'attrs' => [
                    'regex' => '{/profile/(\w+)$}ADu',
                    'parts' => ['/profile/', null],
                    'parameterMap' => [1 => 'name'],
                    'requirements' => ['name' => '{\w+$}ADu'],
                ],
                'matchTestCases' => [
                    '' => null,
                    '/profile/' => null,
                    '/profile/bar' => ['name' => 'bar'],
                    '/profile/baz_qux' => ['name' => 'baz_qux'],
                    '/profile/baz/qux' => null,
                ],
                'generateTestCases' => [
                    [['name' => 'qux'], '/profile/qux'],
                    [['name' => 'lorem_ipsum', 'uknown' => 'dummy'], '/profile/lorem_ipsum'],
                ],
                'expectedDump' => '/profile/{name}',
            ],

            'prefix and regex with params' => [
                'attrs' => [
                    'prefix' => '/page/',
                    'regex' => '{(\d+)-([\w\-]+)$}ADu',
                    'parts' => [null, '-', null],
                    'parameterMap' => [0 => 'id', 2 => 'slug'],
                    'requirements' => ['id' => '{\d+$}ADu', 'slug'=> '{[\w\-]+$}ADu'],
                ],
                'matchTestCases' => [
                    '' => null,
                    '/page/' => null,
                    '/page/5' => null,
                    '/page/foo' => null,
                    '/page/foo-bar' => null,
                    '/egap/5-foo' => null,
                    '/page/5-foo' => ['id' => '5', 'slug' => 'foo'],
                    '/page/123456-lorem-ipsum' => ['id' => '123456', 'slug' => 'lorem-ipsum'],
                ],
                'generateTestCases' => [
                    [['id' => 123, 'slug' => 'foo'], '/page/123-foo'],
                    [['id' => '654321', 'slug' => 'dolor-sit'], '/page/654321-dolor-sit'],
                ],
                'expectedDump' => '/page/{id}-{slug}',
            ],

            'utf8' => [
                'attrs' => [
                    'prefix' => '/žluťoučký/',
                    'regex' => '{(kůň|jednorožec)/(v-lese|na-louce)$}ADu',
                    'parts' => [null, '/', null],
                    'parameterMap' => [0 => 'zvíře', 2 => 'kde'],
                    'requirements' => ['zvíře' => '{(kůň|jednorožec)$}ADu', 'kde' => '{v-lese|na-louce$}ADu'],
                ],
                'matchTestCases' => [
                    '' => null,
                    '/hadraplán' => null,
                    '/žluťoučký/kůň' => null,
                    '/žluťoučký/kůň/v-lese' => ['zvíře' => 'kůň', 'kde' => 'v-lese'],
                    '/žluťoučký/jednorožec/na-louce' => ['zvíře' => 'jednorožec', 'kde' => 'na-louce'],
                ],
                'generateTestCases' => [
                    [['zvíře' => 'kůň', 'kde' => 'v-lese'], '/žluťoučký/kůň/v-lese'],
                    [['zvíře' => 'jednorožec', 'kde' => 'na-louce'], '/žluťoučký/jednorožec/na-louce'],
                ],
                'expectedDump' => '/žluťoučký/{zvíře}/{kde}',
            ],
        ];
    }

    function testShouldThrowExceptionWhenGeneratingWithMissingParameter()
    {
        $pattern = $this->createPattern([
            'regex' => '((\w+)-(\w+)$}ADu',
            'parts' => [null, '-', null],
            'parameterMap' => [0 => 'foo', 2 => 'bar'],
            'requirements' => ['foo' => '{\w+$}ADu', 'bar' => '{\w+$}ADu'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter "bar"');

        $pattern->generate(['foo' => 'test']);
    }

    function testShouldGenerateWithInvalidParameterIfCheckingIsDisabled()
    {
        $pattern = $this->createPattern([
            'regex' => '{(\d+):(\d+)$}ADu',
            'parts' => [null, ':', null],
            'parameterMap' => [0 => 'from', 2 => 'to'],
            'requirements' => ['from' => '{\d+$}ADu', 'to' => '{\d+$}ADu'],
        ]);

        $this->assertSame('not-a-number:foo', $pattern->generate(['from' => 'not-a-number', 'to' => 'foo'], false));

        return $pattern;
    }

    /**
     * @depends testShouldGenerateWithInvalidParameterIfCheckingIsDisabled
     */
    function testShouldThrowExceptionWhenGeneratingInvalidParameterByDefault(Pattern $pattern)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "to" must match "{\d+$}ADu"');

        $pattern->generate(['from' => '123456', 'to' => 'not-a-number']);
    }

    protected function createPattern(array $attrs): Pattern
    {
        return new Pattern(
            $attrs['prefix'] ?? null,
            $attrs['regex'] ?? null,
            $attrs['parts'] ?? [],
            $attrs['parameterMap'] ?? [],
            $attrs['requirements'] ?? []
        );
    }
}
