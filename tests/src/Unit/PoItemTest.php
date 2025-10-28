<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sweetchuck\PoParser\PoHeader;
use Sweetchuck\PoParser\PoItem;

/**
 * @covers \Sweetchuck\PoParser\PoItem
 */
class PoItemTest extends TestCase
{
    public function testCreateFromHeader(): void
    {
        $header = PoHeader::createFromIterable([
            'a' => 'b',
            'c' => 'd',
        ]);
        $item = PoItem::createFromHeader($header);
        static::assertSame(
            [
                'comments' => [],
                'msgctxt' => [],
                'msgid' => [''],
                'msgidPlural' => [],
                'msgstr' => [
                    '' => [
                        'a: b\n',
                        'c: d\n',
                    ],
                ],
            ],
            $item->jsonSerialize(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesToString(): array
    {
        return [
            'empty' => [
                '',
                [],
            ],
            'all-in-one' => [
                implode("\n", [
                    '# Comment 1.',
                    '# Comment 2.',
                    'msgctxt "my-context-01"',
                    'msgid "my-id-01"',
                    'msgid_plural "my-id-plural-01"',
                    'msgstr "my-str-01"',
                    '',
                ]),
                [
                    'comments' => [
                        '# Comment 1.',
                        '# Comment 2.',
                    ],
                    'msgctxt' => [
                        'my-context-01',
                    ],
                    'msgid' => [
                        'my-id-01',
                    ],
                    'msgidPlural' => [
                        'my-id-plural-01',
                    ],
                    'msgstr' => [
                        '' => [
                            'my-str-01',
                        ],
                    ],
                ],
            ],
            'all-in-one-multiline' => [
                implode("\n", [
                    '# Comment 1.',
                    '# Comment 2.',
                    'msgctxt ""',
                    '"my-context-01"',
                    '"my-context-02"',
                    'msgid ""',
                    '"my-id-01"',
                    '"my-id-02"',
                    'msgid_plural ""',
                    '"my-id-plural-01"',
                    '"my-id-plural-02"',
                    'msgstr ""',
                    '"my-str-01"',
                    '"my-str-02"',
                    '',
                ]),
                [
                    'comments' => [
                        '# Comment 1.',
                        '# Comment 2.',
                    ],
                    'msgctxt' => [
                        'my-context-01',
                        'my-context-02',
                    ],
                    'msgid' => [
                        'my-id-01',
                        'my-id-02',
                    ],
                    'msgidPlural' => [
                        'my-id-plural-01',
                        'my-id-plural-02',
                    ],
                    'msgstr' => [
                        '' => [
                            'my-str-01',
                            'my-str-02',
                        ],
                    ],
                ],
            ],
            'with new line' => [
                implode("\n", [
                    'msgid ""',
                    '"line 1\n"',
                    '"line 2\n"',
                    '',
                ]),
                [
                    'msgid' => [
                        'line 1\n',
                        'line 2\n',
                    ],
                ],
            ],
        ];
    }

    /**
     * @phpstan-param array<string, mixed> $state
     *
     * @dataProvider casesToString
     */
    public function testToString(string $expected, array $state): void
    {
        $poItem = PoItem::__set_state($state);

        static::assertSame(
            $expected,
            "$poItem",
        );
    }

    public function testJsonSerialize(): void
    {
        $poItem = new PoItem();
        static::assertSame(
            [
                'comments' => [],
                'msgctxt' => [],
                'msgid' => [],
                'msgidPlural' => [],
                'msgstr' => [],
            ],
            $poItem->jsonSerialize(),
        );

        $poItem->comments = ['# comment 01'];
        $poItem->msgctxt = ['my context'];
        $poItem->msgid = ['my id'];
        $poItem->msgidPlural = ['my id plural'];
        $poItem->msgstr = ['' => ['my str']];
        static::assertSame(
            [
                'comments' => ['# comment 01'],
                'msgctxt' => ['my context'],
                'msgid' => ['my id'],
                'msgidPlural' => ['my id plural'],
                'msgstr' => ['' => ['my str']],
            ],
            $poItem->jsonSerialize(),
        );
    }
}
