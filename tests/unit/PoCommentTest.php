<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use Sweetchuck\PoParser\PoComment;
use Codeception\Test\Unit;
use Sweetchuck\PoParser\PoItem;
use Sweetchuck\PoParser\Tests\UnitTester;

/**
 * @covers \Sweetchuck\PoParser\PoComment
 */
class PoCommentTest extends Unit
{
    protected UnitTester $tester;

    public function casesToString(): array
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
     * @dataProvider casesToString
     */
    public function testToString(string $expected, array $methodCalls): void
    {
        $comment = new PoComment();
        foreach ($methodCalls as $methodCall) {
            $comment->{$methodCall['method']}(...$methodCall['args']);
        }

        $this->assertSame($expected, (string) $comment);
    }

    public function testGetLastId(): void
    {
        $comment = new PoComment();
        $comment->setTranslator('Foo');
        $this->tester->assertSame('translator:0', $comment->getLastId());
        $comment->setTranslator('Bar');
        $this->tester->assertSame('translator:1', $comment->getLastId());
        $comment->setTranslator('Foo changed', 0, 'translator:0');
        $this->tester->assertSame('translator:0', $comment->getLastId());
    }

    public function testSetState(): void
    {
        $comment = PoComment::__set_state([]);
        $this->tester->assertSame(
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
        $this->tester->assertSame(
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

        $this->tester->assertSame(
            implode("\n", [
                '#  Foo',
                '#, fuzzy',
                '',
            ]),
            (string) $comment,
        );

        $comment->delete('flag:fuzzy');
        $this->tester->assertSame(
            implode("\n", [
                '#  Foo',
                '',
            ]),
            (string) $comment,
        );
    }

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
        $this->tester->assertSame(
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
