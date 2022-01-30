<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use Codeception\Test\Unit;
use Sweetchuck\PoParser\PoReader;
use Sweetchuck\PoParser\Tests\UnitTester;

/**
 * @covers \Sweetchuck\PoParser\PoReader
 */
class PoReaderTest extends Unit
{
    protected UnitTester $tester;

    public function casesParse(): array
    {
        $fixturesDir = codecept_data_dir('fixtures');

        return [
            'all-in-one' => [
                file_get_contents("$fixturesDir/po-valid/all-in-one.po"),
            ],
        ];
    }

    /**
     * @dataProvider casesParse
     */
    public function testParse(string $expected): void
    {
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $expected);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        $this->tester->assertSame($expected, "$poReader");
    }

    public function casesParseExtra(): array
    {
        return [
            'empty' => [
                [
                    "msgid" => [''],
                ],
                'fileContent' => implode("\n", [
                    'msgid ""',
                    'msgstr ""',
                ]),
            ],
            'double quote in the middle' => [
                [
                    "msgid" => ['a"b'],
                ],
                'fileContent' => implode("\n", [
                    'msgid "a\"b"',
                    'msgstr "a\"b"',
                ]),
            ],
            'double quote at the end' => [
                [
                    "msgid" => ['a"'],
                ],
                'fileContent' => implode("\n", [
                    'msgid "a\""',
                    'msgstr "a\""',
                ]),
            ],
            'new line in the middle' => [
                [
                    "msgid" => ["a\nb"],
                ],
                'fileContent' => implode("\n", [
                    'msgid "a\nb"',
                    'msgstr "a\nb"',
                ]),
            ],
            'new line at the end' => [
                [
                    "msgid" => ["a\n"],
                ],
                'fileContent' => implode("\n", [
                    'msgid "a\n"',
                    'msgstr "a\n"',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider casesParseExtra
     */
    public function testParseExtra(array $expected, string $fileContent): void
    {
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);
        $poReader->seek(0);
        $poItem = $poReader->current();

        foreach ($expected as $keyword => $expectedValue) {
            $this->tester->assertSame(
                $poItem->$keyword,
                $expectedValue,
                "value of poItem::$keyword is correct",
            );
        }
    }

    public function casesSeekEmpty(): array
    {
        return [
            'empty' => [''],
            'only comment' => [
                implode("\n", [
                    '# Comment 1',
                    '# Comment 2',
                ]),
            ],
            'only empty lines' => [
                implode("\n", [
                    '',
                    '',
                ]),
            ],
            'comments and empty lines' => [
                implode("\n", [
                    '',
                    '# Comment 1',
                    '',
                    '',
                    '# Comment 2',
                    '',
                    '',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider casesSeekEmpty
     */
    public function testSeekEmpty(string $fileContent): void
    {
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        $this->tester->expectThrowable(
            new \OutOfBoundsException(
                'offset has to be >= 0; current -1',
                1,
            ),
            function () use ($poReader) {
                $poReader->seek(-1);
            },
        );
        $this->tester->assertNull($poReader->current());
        $this->tester->assertFalse($poReader->valid());
        $this->tester->assertSame(-1, $poReader->key());

        $this->tester->expectThrowable(
            new \OutOfBoundsException(
                'maximum offset: -1; requested offset: 0',
                1,
            ),
            function () use ($poReader) {
                $poReader->seek(0);
            },
        );
        $this->tester->assertNull($poReader->current());
        $this->tester->assertFalse($poReader->valid());
        $this->tester->assertSame(-1, $poReader->key());

        $this->tester->expectThrowable(
            new \OutOfBoundsException(
                'maximum offset: -1; requested offset: 1',
                1,
            ),
            function () use ($poReader) {
                $poReader->seek(1);
            },
        );
        $this->tester->assertNull($poReader->current());
        $this->tester->assertFalse($poReader->valid());
        $this->tester->assertSame(-1, $poReader->key());
    }

    public function testSeekOneItem(): void
    {
        $fileContent = implode("\n", [
            '# Comment 1',
            'msgid "Hello world"',
            'msgstr "Hello világ"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);
        $this->tester->assertSame($fileHandler, $poReader->getFileHandler());

        $this->tester->expectThrowable(
            new \OutOfBoundsException(
                'offset has to be >= 0; current -1',
                1,
            ),
            function () use ($poReader) {
                $poReader->seek(-1);
            },
        );
        $this->tester->assertNull($poReader->current());
        $this->tester->assertFalse($poReader->valid());
        $this->tester->assertSame(-1, $poReader->key());

        $poReader->seek(0);
        $this->tester->assertNotNull($poReader->current());
        $this->tester->assertTrue($poReader->valid());
        $this->tester->assertSame(0, $poReader->key());

        $this->tester->expectThrowable(
            new \OutOfBoundsException(
                'maximum offset: 0; requested offset: 1',
                1,
            ),
            function () use ($poReader) {
                $poReader->seek(1);
            },
        );
        $this->tester->assertNull($poReader->current());
        $this->tester->assertFalse($poReader->valid());
        $this->tester->assertSame(0, $poReader->key());
    }

    public function testSeekTwoItem(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 0"',
            'msgstr "Hello világ 0"',
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        $poReader->seek(0);
        $poItem = $poReader->current();
        $this->tester->assertSame(0, $poReader->key());
        $this->tester->assertSame(['Hello world 0'], $poItem->msgid);
        $this->tester->assertSame(['' => ['Hello világ 0']], $poItem->msgstr);

        $poReader->next();
        $poItem = $poReader->current();
        $this->tester->assertSame(1, $poReader->key());
        $this->tester->assertSame(['Hello world 1'], $poItem->msgid);
        $this->tester->assertSame(['' => ['Hello világ 1']], $poItem->msgstr);

        $poReader->seek(0);
        $poItem = $poReader->current();
        $this->tester->assertSame(0, $poReader->key());
        $this->tester->assertSame(['Hello world 0'], $poItem->msgid);
        $this->tester->assertSame(['' => ['Hello világ 0']], $poItem->msgstr);

        $poReader->next();
        $poItem = $poReader->current();
        $this->tester->assertSame(1, $poReader->key());
        $this->tester->assertSame(['Hello world 1'], $poItem->msgid);
        $this->tester->assertSame(['' => ['Hello világ 1']], $poItem->msgstr);

        $poReader->seek(1);
        $poItem = $poReader->current();
        $this->tester->assertSame(1, $poReader->key());
        $this->tester->assertSame(['Hello world 1'], $poItem->msgid);
        $this->tester->assertSame(['' => ['Hello világ 1']], $poItem->msgstr);
    }

    public function testJsonSerialize(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
            'msgid "Hello world 2"',
            'msgstr "Hello világ 2"',
            'msgid "Hello world 3"',
            'msgstr "Hello világ 3"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);
        $poReader->seek(1);

        $this->tester->assertSame(
            [
                'key' => 1,
                'positions' => [
                    0 => 0,
                    1 => 46,
                ],
                'isAllReaded' => false,
            ],
            $poReader->jsonSerialize(),
        );
    }

    public function testSetStateWithKey(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 0"',
            'msgstr "Hello világ 0"',
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
            'msgid "Hello world 2"',
            'msgstr "Hello világ 2"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);

        $state = [
            'key' => 1,
            'positions' => [
                0 => 0,
                1 => 46,
            ],
            'isAllReaded' => false,
            'fileHandler' => $fileHandler,
        ];

        $poReader = PoReader::__set_state($state);
        $poItem = $poReader->current();
        $this->tester->assertTrue($poReader->valid());
        $this->tester->assertSame(1, $poReader->key());
        $this->tester->assertSame(['Hello world 1'], $poItem->msgid);
    }

    public function testSetStateWithoutKey(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 0"',
            'msgstr "Hello világ 0"',
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
            'msgid "Hello world 2"',
            'msgstr "Hello világ 2"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);
        fseek($fileHandler, 0);

        $state = [
            'positions' => [
                0 => 0,
                1 => 46,
            ],
            'isAllReaded' => false,
            'fileHandler' => $fileHandler,
        ];

        $poReader = PoReader::__set_state($state);
        $poItem = $poReader->current();
        $this->tester->assertTrue($poReader->valid());
        $this->tester->assertSame(0, $poReader->key());
        $this->tester->assertSame(['Hello world 0'], $poItem->msgid);
    }

    public function testSetStateContinue(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 0"',
            'msgstr "Hello világ 0"',
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
            'msgid "Hello world 2"',
            'msgstr "Hello világ 2"',
        ]);

        $state = [];
        $result = $this->readByState($state, $fileContent, 2);
        $this->assertSame(
            [
                'msgidList' => [
                    'Hello world 0',
                    'Hello world 1',
                ],
                'state' => [
                    'key' => 2,
                    'positions' => [
                        0 => 0,
                        1 => 46,
                        2 => 92,
                    ],
                    'isAllReaded' => false,
                ],
            ],
            $result,
        );

        $result = $this->readByState($result['state'], $fileContent, 2);
        $this->assertSame(
            [
                'msgidList' => [
                    'Hello world 2',
                ],
                'state' => [
                    'key' => 2,
                    'positions' => [
                        0 => 0,
                        1 => 46,
                        2 => 92,
                        3 => 136,
                    ],
                    'isAllReaded' => true,
                ],
            ],
            $result,
        );
    }

    protected function readByState(array $state, string $fileContent, int $limit): array
    {
        $fileHandler = fopen('php://memory', 'w+');
        fwrite($fileHandler, $fileContent);
        fseek($fileHandler, 0);

        $result = [
            'msgidList' => [],
        ];
        $state['fileHandler'] = $fileHandler;
        $poReader = PoReader::__set_state($state);
        while ($limit > 0 && $poReader->valid()) {
            $poItem = $poReader->current();
            $result['msgidList'][] = $poItem->msgid[0];
            $limit--;
            $poReader->next();
        }

        $result['state'] = $poReader->jsonSerialize();

        return $result;
    }

    public function testSeekWithoutPositions(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 0"',
            'msgstr "Hello világ 0"',
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
            'msgid "Hello world 2"',
            'msgstr "Hello világ 2"',
        ]);
        $fileHandler1 = fopen('php://memory', 'w+');
        fwrite($fileHandler1, $fileContent);

        fseek($fileHandler1, 46);
        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler1);
        $poReader->next();
        $poItem = $poReader->current();
        $this->tester->assertSame(0, $poReader->key());
        $this->tester->assertSame(['Hello world 1'], $poItem->msgid);

        $poReader->rewind();
        $poItem = $poReader->current();
        $this->tester->assertSame(0, $poReader->key());
        $this->tester->assertSame(['Hello world 1'], $poItem->msgid);
    }
}
