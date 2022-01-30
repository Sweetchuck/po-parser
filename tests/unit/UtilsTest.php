<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use Codeception\Test\Unit;
use Sweetchuck\PoParser\Tests\UnitTester;
use Sweetchuck\PoParser\Utils;

/**
 * @covers \Sweetchuck\PoParser\Utils
 */
class UtilsTest extends Unit
{
    protected UnitTester $tester;

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
     * @dataProvider casesLinesToPo
     */
    public function testLinesToPo(string $expected, array $lines): void
    {
        $this->tester->assertSame(
            $expected,
            Utils::linesToPo($lines),
        );
    }

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
     * @dataProvider casesExplode
     */
    public function testExplode(array $expected, string $string): void
    {
        $this->tester->assertSame(
            $expected,
            Utils::explode($string),
        );
    }
}
