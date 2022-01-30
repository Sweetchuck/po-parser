<?php

declare(strict_types = 1);

use Sweetchuck\PoParser\PoHeader;
use Sweetchuck\PoParser\PoReader;

$fileContent = <<<'PO'
# Comment 1.
# Comment 2.
msgid ""
msgstr ""
"Project-Id-Version: MyProject (1.0.0)\n"
"POT-Creation-Date: 2020-03-03 23:20+0100\n"
"PO-Revision-Date: 2020-03-03 23:54+0100\n"
"Language-Team: Hungarian\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n!=1);\n"
"Last-Translator: M <me@example.comy\n"
"Language: hu_HU\n"
"X-Generator: My hand\n"

msgid "Forms"
msgstr "Űrlapok"

msgid "1 minute"
msgid_plural "@count minutes"
msgstr[0] "1 perc"
msgstr[1] "@count perc"

msgctxt "Sort order"
msgid "Order"
msgstr "Rendezés"

msgid ""
"Line one\n"
"Line two\n"
msgstr ""
"Line one\n"
"Line two\n"
PO;

require __DIR__ . '/../../vendor/autoload.php';

$fileHandler = fopen('php://memory', 'w+');
fwrite($fileHandler, $fileContent);

$reader = new PoReader();
$reader->setFileHandler($fileHandler);
$reader->seek(0);

$item = $reader->current();
$reader->next();

$header = PoHeader::createFromItem($item);
dump($header);

// Common keys are available by get/set methods.
echo 'Method - Project-Id-Version => ', $header->getProjectIdVersion(), PHP_EOL;

// All keys are available to read/write by case-insensitive \ArrayAccess.
echo 'CamelCase - Project-Id-Version => ', $header['Project-Id-Version'], PHP_EOL;
echo 'LowerCase - Project-Id-Version => ', $header['project-id-version'], PHP_EOL;

// PoHeader is iterable.
foreach ($header as $key => $value) {
    echo "loop - $key => $value", PHP_EOL;
}

while ($reader->valid()) {
    $item = $reader->current();
    echo '-------------', PHP_EOL;
    dump($item);
    echo '-------------', PHP_EOL;
    $reader->next();
}
