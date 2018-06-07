<?php declare(strict_types=1);

namespace Kuria\Router\Route;

class PathPatternTest extends PatternTest
{
    function providePatterns(): array
    {
        $patterns = parent::providePatterns();

        $patterns['utf8']['generateTestCases'] = [
            [['zvíře' => 'kůň', 'kde' => 'v-lese'], '/%C5%BElu%C5%A5ou%C4%8Dk%C3%BD/k%C5%AF%C5%88/v-lese'],
            [['zvíře' => 'jednorožec', 'kde' => 'na-louce'], '/%C5%BElu%C5%A5ou%C4%8Dk%C3%BD/jednoro%C5%BEec/na-louce'],
        ];

        $patterns['raw special characters'] = [
            'attrs' => [
                'prefix' => '/!*+,/:;=@|/',
                'regex' => '{(.+)/\|@\=;\:/,\+\*\!$}ADu',
                'parts' => [null, '/|@=;:/,+*!'],
                'parameterMap' => [0 => 'str'],
                'requirements' => ['str' => '{.+$}ADu'],
            ],
            'matchTestCases' => [
                '' => null,
                '/!*+,/:;=@|' => null,
                '/!*+,/:;=@|/foo/|@=;:/,+*!' => ['str' => 'foo'],
            ],
            'generateTestCases' => [
                [['str' => 'bar'], '/!*+,/:;=@|/bar/|@=;:/,+*!'],
                [['str' => '*/,|:;@=!+'], '/!*+,/:;=@|/*/,|:;@=!+/|@=;:/,+*!'],
            ],
            'expectedDump' => '/!*+,/:;=@|/{str}/|@=;:/,+*!',
        ];

        return $patterns;
    }

    function testShouldEncodeParts()
    {
        $allowedRawChars = ['!', '*', '+', ',', '/',  ':',  ';',  '=',  '@',  '|'];

        for ($i = 32; $i < 128; ++$i) {
            $char = chr($i);
            $quotedChar = preg_quote($char);

            $pattern = $this->createPattern([
                'prefix' => "/{$char}/",
                'regex' => "{({$quotedChar})/{$quotedChar}$}ADu",
                'parts' => [null, "/{$char}"],
                'parameterMap' => [0 => 'char'],
                'requirements' => ['char' => "{{$quotedChar}$}ADu"],
            ]);

            $expectedEncodedChar = in_array($char, $allowedRawChars, true)
                ? $char
                : rawurlencode($char);

            $this->assertSame(
                sprintf('/%s/%1$s/%1$s', $expectedEncodedChar),
                $pattern->generate(['char' => $char])
            );
        }
    }

    protected function createPattern(array $attrs): Pattern
    {
        return new PathPattern(
            $attrs['prefix'] ?? null,
            $attrs['regex'] ?? null,
            $attrs['parts'] ?? [],
            $attrs['parameterMap'] ?? [],
            $attrs['requirements'] ?? []
        );
    }
}
