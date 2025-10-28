<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sweetchuck\PoParser\Utils;

/**
 * @covers \Sweetchuck\PoParser\Utils
 */
class UtilsTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    public function casesLinesToPo(): array
    {
        return [
            'empty' => [
                '""' . "\n",
                [],
            ],
            'single line' => [
                '"a"' . "\n",
                [
                    'a',
                ],
            ],
            'multiple lines' => [
                implode(
                    "\n",
                    [
                        '""',
                        '"a"',
                        '"b"',
                        '',
                    ],
                ),
                [
                    'a',
                    'b',
                ],
            ],
        ];
    }

    /**
     * @phpstan-param array<string> $lines
     *
     * @dataProvider casesLinesToPo
     */
    public function testLinesToPo(string $expected, array $lines): void
    {
        static::assertSame(
            $expected,
            Utils::linesToPo($lines),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesExplode(): array
    {
        return [
            'empty' => [
                [''],
                '',
            ],
            'short simple' => [
                ['okay'],
                'okay',
            ],
            'short with new lines' => [
                [
                    'one\n',
                    'two',
                ],
                'one\ntwo',
            ],
            'short with escaped new line' => [
                [
                    'one\\\\ntwo',
                ],
                'one\\\\ntwo',
            ],
            'long simple' => [
                [
                    'aaaaaaaaaaaa bbbbbbbbbbbb cccccccccccc dddddddddddd eeeeeeeeeeee ',
                    'ffffffffffff gggggggggggg',
                ],
                'aaaaaaaaaaaa bbbbbbbbbbbb cccccccccccc dddddddddddd eeeeeeeeeeee ffffffffffff gggggggggggg',
            ],
            'long with new line after limit' => [
                [
                    'aaaaaaaaaaaa bbbbbbbbbbbb cccccccccccc dddddddddddd eeeeeeeeeeee ',
                    'ffffffffffff\n',
                    'gggggggggggg',
                ],
                'aaaaaaaaaaaa bbbbbbbbbbbb cccccccccccc dddddddddddd eeeeeeeeeeee ffffffffffff\ngggggggggggg',
            ],
            'long with new line before limit' => [
                [
                    'aaaaaaaaaaaa\n',
                    'bbbbbbbbbbbb cccccccccccc dddddddddddd eeeeeeeeeeee ffffffffffff ',
                    'gggggggggggg',
                ],
                'aaaaaaaaaaaa\nbbbbbbbbbbbb cccccccccccc dddddddddddd eeeeeeeeeeee ffffffffffff gggggggggggg',
            ],
            'ends with new line' => [
                [
                    'a\n',
                    'b\n',
                    'c\n',
                ],
                'a\nb\nc\n',
            ],
        ];
    }

    /**
     * @phpstan-param array<string> $expected
     *
     * @dataProvider casesExplode
     */
    public function testExplode(array $expected, string $string): void
    {
        static::assertSame(
            $expected,
            Utils::explode($string),
        );
    }
}
