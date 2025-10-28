<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sweetchuck\PoParser\PoComment;
use Sweetchuck\PoParser\PoItem;

#[CoversClass(PoComment::class)]
class PoCommentTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    public static function casesToString(): array
    {
        return [
            'empty' => [
                '',
                [],
            ],
            'all-in-one' => [
                implode("\n", [
                    '#  Foo',
                    '#',
                    '#, fuzzy',
                    '#, range 1..100',
                    '#| msgid "my-msgid-01"',
                    '#| msgstr "my-msgstr-01"',
                    '#. My extracted line 01',
                    '#. My extracted line 02',
                    '#: src/a.php src/b.php',
                    '',
                ]),
                [
                    [
                        'method' => 'setTranslator',
                        'args' => [
                            'Foo',
                        ],
                    ],
                    [
                        'method' => 'setTranslator',
                        'args' => [
                            '',
                        ],
                    ],
                    [
                        'method' => 'setFlag',
                        'args' => [
                            'fuzzy',
                        ],
                    ],
                    [
                        'method' => 'setFlag',
                        'args' => [
                            'range',
                            '1..100',
                        ],
                    ],
                    [
                        'method' => 'setPrevious',
                        'args' => [
                            PoItem::__set_state([
                                'msgid' => ['my-msgid-01'],
                                'msgstr' => ['' => ['my-msgstr-01']],
                            ]),
                        ],
                    ],
                    [
                        'method' => 'setExtracted',
                        'args' => [
                            "My extracted line 01\nMy extracted line 02\n",
                        ],
                    ],
                    [
                        'method' => 'setReference',
                        'args' => [
                            [
                                'src/a.php',
                                'src/b.php',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @phpstan-param array<array{method: string, args: array<mixed>}> $methodCalls
     */
    #[DataProvider('casesToString')]
    #[Test]
    public function testToString(string $expected, array $methodCalls): void
    {
        $comment = new PoComment();
        foreach ($methodCalls as $methodCall) {
            $comment->{$methodCall['method']}(...$methodCall['args']);
        }

        static::assertSame($expected, (string) $comment);
    }

    #[Test]
    public function testGetLastId(): void
    {
        $comment = new PoComment();
        $comment->setTranslator('Foo');
        static::assertSame('translator:0', $comment->getLastId());
        $comment->setTranslator('Bar');
        static::assertSame('translator:1', $comment->getLastId());
        $comment->setTranslator('Foo changed', 0, 'translator:0');
        static::assertSame('translator:0', $comment->getLastId());
    }

    #[Test]
    public function testSetState(): void
    {
        $comment = PoComment::__set_state([]);
        static::assertSame(
            [
                'items' => [],
                'counters' => [
                    'translator' => 0,
                    'previous' => 0,
                    'extracted' => 0,
                    'reference' => 0,
                ],
            ],
            $comment->jsonSerialize(),
        );

        $comment = PoComment::__set_state([
            'items' => [
                'translator:0' => [
                    'weight' => 0,
                    'type' => 'translator',
                    'comment' => 'Foo',
                ],
                'flag:fuzzy' => [
                    'weight' => 0,
                    'type' => 'flag',
                    'flag' => 'fuzzy',
                    'comment' => '',
                ],
            ],
            'counters' => [
                'translator' => 1,
                'previous' => 0,
                'extracted' => 0,
                'reference' => 0,
            ],
        ]);
        static::assertSame(
            [
                'items' => [
                    'translator:0' => [
                        'weight' => 0,
                        'type' => 'translator',
                        'comment' => 'Foo',
                    ],
                    'flag:fuzzy' => [
                        'weight' => 0,
                        'type' => 'flag',
                        'flag' => 'fuzzy',
                        'comment' => '',
                    ],
                ],
                'counters' => [
                    'translator' => 1,
                    'previous' => 0,
                    'extracted' => 0,
                    'reference' => 0,
                ],
            ],
            $comment->jsonSerialize(),
        );

        static::assertSame(
            implode("\n", [
                '#  Foo',
                '#, fuzzy',
                '',
            ]),
            (string) $comment,
        );

        $comment->delete('flag:fuzzy');
        static::assertSame(
            implode("\n", [
                '#  Foo',
                '',
            ]),
            (string) $comment,
        );
    }

    #[Test]
    public function testRealLife(): void
    {
        $comment = new PoComment();
        $comment
            ->setTranslator('Foo')
            ->setTranslator('')
            ->setTranslator('Long description line 1')
            ->setTranslator('Long description line 2')
            ->setReference(['src/a.php', 'src/b.php']);
        $item = new PoItem();
        $item->comments = $comment->toItemValue();
        $item->msgid = ['my-id-01'];
        $item->msgstr = ['' => ['my-str-01']];
        static::assertSame(
            implode("\n", [
                '#  Foo',
                '#',
                '#  Long description line 1',
                '#  Long description line 2',
                '#: src/a.php src/b.php',
                'msgid "my-id-01"',
                'msgstr "my-str-01"',
                '',
            ]),
            (string) $item,
        );
    }
}
