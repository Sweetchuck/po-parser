<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sweetchuck\PoParser\PoReader;

#[CoversClass(PoReader::class)]
class PoReaderTest extends TestCase
{
    protected function getFixturesDir(): string
    {
        return dirname(__DIR__, 2) . '/fixtures';
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesParse(): array
    {
        $fixturesDir = dirname(__DIR__, 2) . '/fixtures';
        $cases = [];
        // @phpstan-ignore-next-line
        foreach (glob("$fixturesDir/po-valid/*.po") as $fileName) {
            $inputPo = file_get_contents($fileName);
            if ($inputPo === false) {
                throw new \RuntimeException("Cannot read file $fileName");
            }
            $cases[basename($fileName)] = [
                static::convertInputPoToExpected($inputPo),
                $inputPo,
            ];
        }

        return $cases;
    }

    /**
     */
    #[DataProvider('casesParse')]
    #[Test]
    public function testParse(string $expected, string $inputPo): void
    {
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $inputPo);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        static::assertSame(
            $expected,
            (string) $poReader,
        );

        fclose($fileHandler);
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesParseExtra(): array
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
     * @phpstan-param array<string, mixed> $expected
     */
    #[DataProvider('casesParseExtra')]
    #[Test]
    public function testParseExtra(array $expected, string $fileContent): void
    {
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);
        $poReader->seek(0);
        $poItem = $poReader->current();

        foreach ($expected as $keyword => $expectedValue) {
            static::assertSame(
                $poItem->$keyword,
                $expectedValue,
                "value of poItem::$keyword is correct",
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesSeekEmpty(): array
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
     */
    #[DataProvider('casesSeekEmpty')]
    #[Test]
    public function testSeekEmpty(string $fileContent): void
    {
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        try {
            // @phpstan-ignore-next-line
            $poReader->seek(-1);
            $this->fail('Expected OutOfBoundsException');
        } catch (\OutOfBoundsException $e) {
            static::assertSame('offset has to be >= 0; current -1', $e->getMessage());
            static::assertSame(1, $e->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());

        try {
            $poReader->seek(0);
            $this->fail('Expected OutOfBoundsException');
        } catch (\Throwable $exception) {
            static::assertInstanceOf(\OutOfBoundsException::class, $exception);
            static::assertSame('maximum offset: -1; requested offset: 0', $exception->getMessage());
            static::assertSame(1, $exception->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());

        try {
            $poReader->seek(1);
            $this->fail('Expected OutOfBoundsException');
        } catch (\Throwable $exception) {
            static::assertInstanceOf(\OutOfBoundsException::class, $exception);
            static::assertSame('maximum offset: -1; requested offset: 1', $exception->getMessage());
            static::assertSame(1, $exception->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());
    }

    #[Test]
    public function testSeekEmptyEmpty(): void
    {
        $fileContent = '';
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        try {
            // @phpstan-ignore-next-line
            $poReader->seek(-1);
            $this->fail('Expected OutOfBoundsException');
        } catch (\OutOfBoundsException $e) {
            static::assertSame('offset has to be >= 0; current -1', $e->getMessage());
            static::assertSame(1, $e->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());

        try {
            $poReader->seek(0);
            $this->fail('Expected OutOfBoundsException');
        } catch (\OutOfBoundsException $e) {
            static::assertSame('maximum offset: -1; requested offset: 0', $e->getMessage());
            static::assertSame(1, $e->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());

        try {
            $poReader->seek(1);
            $this->fail('Expected OutOfBoundsException');
        } catch (\OutOfBoundsException $e) {
            static::assertSame('maximum offset: -1; requested offset: 1', $e->getMessage());
            static::assertSame(1, $e->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());
    }

    #[Test]
    public function testSeekOneItem(): void
    {
        $fileContent = implode("\n", [
            '# Comment 1',
            'msgid "Hello world"',
            'msgstr "Hello világ"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);
        static::assertSame($fileHandler, $poReader->getFileHandler());

        try {
            // @phpstan-ignore-next-line
            $poReader->seek(-1);
            $this->fail('Expected OutOfBoundsException');
        } catch (\OutOfBoundsException $error) {
            static::assertSame('offset has to be >= 0; current -1', $error->getMessage());
            static::assertSame(1, $error->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(-1, $poReader->key());

        $poReader->seek(0);
        static::assertNotNull($poReader->current());
        static::assertTrue($poReader->valid());
        static::assertSame(0, $poReader->key());

        try {
            $poReader->seek(1);
            $this->fail('Expected OutOfBoundsException');
        } catch (\OutOfBoundsException $error) {
            static::assertSame('maximum offset: 0; requested offset: 1', $error->getMessage());
            static::assertSame(1, $error->getCode());
        }

        static::assertNull($poReader->current());
        static::assertFalse($poReader->valid());
        static::assertSame(0, $poReader->key());
    }

    #[Test]
    public function testSeekTwoItem(): void
    {
        $fileContent = implode("\n", [
            'msgid "Hello world 0"',
            'msgstr "Hello világ 0"',
            'msgid "Hello world 1"',
            'msgstr "Hello világ 1"',
        ]);
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);

        $poReader->seek(0);
        $poItem = $poReader->current();
        static::assertSame(0, $poReader->key());
        static::assertSame(['Hello world 0'], $poItem->msgid);
        static::assertSame(['' => ['Hello világ 0']], $poItem->msgstr);

        $poReader->next();
        $poItem = $poReader->current();
        static::assertSame(1, $poReader->key());
        static::assertSame(['Hello world 1'], $poItem->msgid);
        static::assertSame(['' => ['Hello világ 1']], $poItem->msgstr);

        $poReader->seek(0);
        $poItem = $poReader->current();
        static::assertSame(0, $poReader->key());
        static::assertSame(['Hello world 0'], $poItem->msgid);
        static::assertSame(['' => ['Hello világ 0']], $poItem->msgstr);

        $poReader->next();
        $poItem = $poReader->current();
        static::assertSame(1, $poReader->key());
        static::assertSame(['Hello world 1'], $poItem->msgid);
        static::assertSame(['' => ['Hello világ 1']], $poItem->msgstr);

        $poReader->seek(1);
        $poItem = $poReader->current();
        static::assertSame(1, $poReader->key());
        static::assertSame(['Hello world 1'], $poItem->msgid);
        static::assertSame(['' => ['Hello világ 1']], $poItem->msgstr);
    }

    #[Test]
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
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);

        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler);
        $poReader->seek(1);

        static::assertSame(
            [
                'positions' => [
                    0 => 0,
                    1 => 46,
                ],
                'isAllReaded' => false,
                'key' => 1,
            ],
            $poReader->jsonSerialize(),
        );
    }

    #[Test]
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
        static::assertIsResource($fileHandler);
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
        static::assertTrue($poReader->valid());
        static::assertSame(1, $poReader->key());
        static::assertSame(['Hello world 1'], $poItem->msgid);
    }

    #[Test]
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
        static::assertIsResource($fileHandler);
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
        static::assertTrue($poReader->valid());
        static::assertSame(0, $poReader->key());
        static::assertSame(['Hello world 0'], $poItem->msgid);
    }

    #[Test]
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
        static::assertSame(
            [
                'msgidList' => [
                    'Hello world 0',
                    'Hello world 1',
                ],
                'state' => [
                    'positions' => [
                        0 => 0,
                        1 => 46,
                        2 => 92,
                    ],
                    'isAllReaded' => false,
                    'key' => 2,
                ],
            ],
            $result,
        );

        $result = $this->readByState($result['state'], $fileContent, 2);
        static::assertSame(
            [
                'msgidList' => [
                    'Hello world 2',
                ],
                'state' => [
                    'positions' => [
                        0 => 0,
                        1 => 46,
                        2 => 92,
                        3 => 136,
                    ],
                    'isAllReaded' => true,
                    'key' => 2,
                ],
            ],
            $result,
        );
    }

    #[Test]
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
        static::assertIsResource($fileHandler1);
        fwrite($fileHandler1, $fileContent);

        fseek($fileHandler1, 46);
        $poReader = new PoReader();
        $poReader->setFileHandler($fileHandler1);
        $poReader->next();
        $poItem = $poReader->current();
        static::assertSame(0, $poReader->key());
        static::assertSame(['Hello world 1'], $poItem->msgid);

        $poReader->rewind();
        $poItem = $poReader->current();
        static::assertSame(0, $poReader->key());
        static::assertSame(['Hello world 1'], $poItem->msgid);
    }

    /**
     * @phpstan-param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    protected function readByState(array $state, string $fileContent, int $limit): array
    {
        $fileHandler = fopen('php://memory', 'w+');
        if (!$fileHandler) {
            throw new \RuntimeException('Cannot open file handler');
        }
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

    protected static function convertInputPoToExpected(string $inputPo): string
    {
        return preg_replace(
            [
                '/\n{2,}$/',
                '/\n{3,}/',
            ],
            [
                "\n",
                "\n\n",
            ],
            $inputPo,
        );
    }
}
