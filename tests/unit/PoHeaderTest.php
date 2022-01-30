<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser\Tests\Unit;

use Codeception\Test\Unit;
use Sweetchuck\PoParser\PoHeader;
use Sweetchuck\PoParser\PoItem;
use Sweetchuck\PoParser\PoReader;
use Sweetchuck\PoParser\Tests\UnitTester;

/**
 * @covers \Sweetchuck\PoParser\PoHeader
 */
class PoHeaderTest extends Unit
{
    protected UnitTester $tester;

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

        $this->tester->assertSame(
            $headerKeyValuePairs,
            $header->jsonSerialize(),
        );

        $this->tester->assertSame($headerString, (string) $header);
        $this->tester->assertSame(
            $headerKeyValuePairs,
            (PoHeader::createFromString($headerString))->jsonSerialize(),
        );

        $this->tester->assertCount(count($headerKeyValuePairs), $header);
        foreach ($header as $key => $value) {
            $this->tester->assertSame($headerKeyValuePairs[$key], $value);
        }

        $this->tester->assertNull($header['nope']);

        foreach (['My-Key-01', 'my-key-01', 'MY-KEY-01', 'my-kEY-01'] as $key) {
            $this->tester->assertSame('value1', $header[$key]);
            $this->tester->assertSame('value1', $header->offsetGet($key));
        }

        foreach (['My-Key-02', 'my-key-02', 'MY-KEY-02', 'my-kEY-02'] as $key) {
            $this->tester->assertSame('value2', $header[$key]);
            $this->tester->assertSame('value2', $header->offsetGet($key));
        }

        $header->offsetSet('mY-key-01', 'value1-new');
        $this->tester->assertSame('value1-new', $header['mY-KEY-01']);

        $header['my-key-01'] = null;
        $this->tester->assertFalse($header->offsetExists('mY-key-01'));
        $this->tester->assertNull($header->offsetGet('mY-key-01'));
    }

    public function testCommonKeys(): void
    {
        $header = new PoHeader();

        $header->setProjectIdVersion('a');
        $this->tester->assertSame('a', $header->getProjectIdVersion());
        $this->tester->assertSame('a', $header['Project-Id-version']);

        $header->setReportMsgidBugsTo('b');
        $this->tester->assertSame('b', $header->getReportMsgidBugsTo());

        $header->setPotCreationDate('c');
        $this->tester->assertSame('c', $header->getPotCreationDate());

        $header->setPoRevisionDate('d');
        $this->tester->assertSame('d', $header->getPoRevisionDate());

        $header->setLastTranslator('e');
        $this->tester->assertSame('e', $header->getLastTranslator());

        $header->setLanguageTeam('f');
        $this->tester->assertSame('f', $header->getLanguageTeam());

        $header->setLanguage('g');
        $this->tester->assertSame('g', $header->getLanguage());

        $header->setContentType('h');
        $this->tester->assertSame('h', $header->getContentType());

        $header->setContentTransferEncoding('i');
        $this->tester->assertSame('i', $header->getContentTransferEncoding());

        $header->setMimeVersion('j');
        $this->tester->assertSame('j', $header->getMimeVersion());

        $header->setPluralForms('k');
        $this->tester->assertSame('k', $header->getPluralForms());
    }

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
        fwrite($fileHandler, $fileContent);
        $poReader->setFileHandler($fileHandler);

        $poReader->seek(0);
        $poItem = $poReader->current();
        $header = PoHeader::createFromItem($poItem);

        $this->tester->assertSame('MyProject01 (1.2.3)', $header->getProjectIdVersion());
        $this->tester->assertSame('text/plain; charset=UTF-8', $header->getContentType());
        $this->tester->assertSame('8bit', $header->getContentTransferEncoding());
        $this->tester->assertSame('hu_HU', $header->getLanguage());
        $this->tester->assertSame('nplurals=2; plural=(n!=1);', $header->getPluralForms());

        $poItem2 = PoItem::createFromHeader($header);
        $this->tester->assertSame(
            $poItem2->msgstr,
            [
                '' => [
                    'Project-Id-Version: MyProject01 (1.2.3)\n',
                    'Content-Type: text/plain; charset=UTF-8\n',
                    'Content-Transfer-Encoding: 8bit\n',
                    'Language: hu_HU\n',
                    'Plural-Forms: nplurals=2; plural=(n!=1);\n',
                ],
            ],
        );
    }
}
