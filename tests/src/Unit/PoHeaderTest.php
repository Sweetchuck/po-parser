<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sweetchuck\PoParser\PoHeader;
use Sweetchuck\PoParser\PoItem;
use Sweetchuck\PoParser\PoReader;

#[CoversClass(PoHeader::class)]
class PoHeaderTest extends TestCase
{
    #[Test]
    public function testAllInOne(): void
    {
        $headerKeyValuePairs = [
            'My-Key-01' => 'value1',
            'My-Key-02' => 'value2',
        ];
        $headerString = implode("\n", [
            'My-Key-01: value1',
            'My-Key-02: value2',
            '',
        ]);
        $header = PoHeader::createFromIterable($headerKeyValuePairs);

        static::assertSame(
            $headerKeyValuePairs,
            $header->jsonSerialize(),
        );

        static::assertSame($headerString, (string) $header);
        static::assertSame(
            $headerKeyValuePairs,
            (PoHeader::createFromString($headerString))->jsonSerialize(),
        );

        static::assertCount(count($headerKeyValuePairs), $header);
        foreach ($header as $key => $value) {
            static::assertSame($headerKeyValuePairs[$key], $value);
        }

        static::assertNull($header['nope']);

        foreach (['My-Key-01', 'my-key-01', 'MY-KEY-01', 'my-kEY-01'] as $key) {
            static::assertSame('value1', $header[$key]);
            static::assertSame('value1', $header->offsetGet($key));
        }

        foreach (['My-Key-02', 'my-key-02', 'MY-KEY-02', 'my-kEY-02'] as $key) {
            static::assertSame('value2', $header[$key]);
            static::assertSame('value2', $header->offsetGet($key));
        }

        $header->offsetSet('mY-key-01', 'value1-new');
        static::assertSame('value1-new', $header['mY-KEY-01']);

        // @phpstan-ignore-next-line
        $header['my-key-01'] = null;
        static::assertFalse($header->offsetExists('mY-key-01'));
        static::assertNull($header->offsetGet('mY-key-01'));
    }

    #[Test]
    public function testCommonKeys(): void
    {
        $header = new PoHeader();

        $header->setProjectIdVersion('a');
        static::assertSame('a', $header->getProjectIdVersion());
        static::assertSame('a', $header['Project-Id-version']);

        $header->setReportMsgidBugsTo('b');
        static::assertSame('b', $header->getReportMsgidBugsTo());

        $header->setPotCreationDate('c');
        static::assertSame('c', $header->getPotCreationDate());

        $header->setPoRevisionDate('d');
        static::assertSame('d', $header->getPoRevisionDate());

        $header->setLastTranslator('e');
        static::assertSame('e', $header->getLastTranslator());

        $header->setLanguageTeam('f');
        static::assertSame('f', $header->getLanguageTeam());

        $header->setLanguage('g');
        static::assertSame('g', $header->getLanguage());

        $header->setContentType('h');
        static::assertSame('h', $header->getContentType());

        $header->setContentTransferEncoding('i');
        static::assertSame('i', $header->getContentTransferEncoding());

        $header->setMimeVersion('j');
        static::assertSame('j', $header->getMimeVersion());

        $header->setPluralForms('k');
        static::assertSame('k', $header->getPluralForms());
    }

    #[Test]
    public function testLifeCycle(): void
    {
        $fileContent = implode("\n", [
            'msgid ""',
            'msgstr ""',
            '"Project-Id-Version: MyProject01 (1.2.3)\n"',
            '"Content-Type: text/plain; charset=UTF-8\n"',
            '"Content-Transfer-Encoding: 8bit\n"',
            '"Language: hu_HU\n"',
            '"Plural-Forms: nplurals=2; plural=(n!=1);\n"',
        ]);
        $poReader = new PoReader();
        $fileHandler = fopen('php://memory', 'w+');
        static::assertIsResource($fileHandler);
        fwrite($fileHandler, $fileContent);
        $poReader->setFileHandler($fileHandler);

        $poReader->seek(0);
        $poItem = $poReader->current();
        $header = PoHeader::createFromItem($poItem);

        static::assertSame('MyProject01 (1.2.3)', $header->getProjectIdVersion());
        static::assertSame('text/plain; charset=UTF-8', $header->getContentType());
        static::assertSame('8bit', $header->getContentTransferEncoding());
        static::assertSame('hu_HU', $header->getLanguage());
        static::assertSame('nplurals=2; plural=(n!=1);', $header->getPluralForms());

        $poItem2 = PoItem::createFromHeader($header);
        static::assertSame(
            [
                '' => [
                    'Project-Id-Version: MyProject01 (1.2.3)\n',
                    'Content-Type: text/plain; charset=UTF-8\n',
                    'Content-Transfer-Encoding: 8bit\n',
                    'Language: hu_HU\n',
                    'Plural-Forms: nplurals=2; plural=(n!=1);\n',
                ],
            ],
            $poItem2->msgstr,
        );
    }
}
