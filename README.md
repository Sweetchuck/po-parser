# Gettext PO Parser

[![CircleCI][badge-circleci]][link-circleci]
[![codecov][badge-codecov]][link-codecov]

A PHP library for parsing and reading Gettext PO and POT files.
Parse translations, headers, and message context from localization files programmatically.


## Features

- Parse Gettext PO/POT files efficiently
- Read translation metadata and headers
- Support for pluralization forms
- Message context (msgctxt) support
- Multiline string handling
- Seekable file iteration for large files
- Memory-efficient streaming parser


## Quick Start

```php
use Sweetchuck\PoParser\PoReader;
use Sweetchuck\PoParser\PoHeader;

// Open a PO file.
$fileHandler = fopen('translations.hu.po', 'r');
$reader = new PoReader();
$reader->setFileHandler($fileHandler);

// Read and process all items.
while ($reader->valid()) {
    $item = $reader->current();
    echo $item->getMsgid();
    echo $item->getMsgstr();
    $reader->next();
}

// Or read the header.
$reader->seek(0);
$headerItem = $reader->current();
$header = PoHeader::createFromItem($headerItem);
echo $header->getProjectIdVersion();
```


## Usage

### Parsing Files

```php
$fileHandler = fopen('path/to/file.po', 'r');
$reader = new PoReader();
$reader->setFileHandler($fileHandler);

// Iterate through all messages
$reader->rewind();
while ($reader->valid()) {
    $item = $reader->current();
    // Process $item
    $reader->next();
}
```


### Accessing Message Data

Each `PoItem` contains:

- `getMsgid()` - Original message string
- `getMsgstr()` - Translated message string(s)
- `getMsgctxt()` - Message context
- `getMsgidPlural()` - Plural form of the message
- `getComments()` - Associated comments


### Working with Headers

```php
$header = PoHeader::createFromItem($item);
echo $header->getProjectIdVersion();
echo $header['Language'];
echo $header['Content-Type'];

// Iterate through all header fields
foreach ($header as $key => $value) {
    echo "$key: $value";
}
```

### Seeking to Specific Items

```php
$reader->seek(5);
$item = $reader->current();
```


### Generating PO Files

You can create and manipulate PO file content programmatically:

```php
use Sweetchuck\PoParser\PoComment;
use Sweetchuck\PoParser\PoReader;
use Sweetchuck\PoParser\PoHeader;
use Sweetchuck\PoParser\PoItem;

// Create a header item
$header = new PoHeader();
$header->setProjectIdVersion('MyProject (1.0.0)');
// or
$header['Project-Id-Version'] = 'MyProject (1.0.0)';
$header['Language'] = 'hu_HU';
$header['Content-Type'] = 'text/plain; charset=UTF-8';
$header['Plural-Forms'] = 'nplurals=2; plural=(n!=1);';

$item0 = PoItem::createFromHeader($header);

// Create a simple message item.
$item1 = PoItem::__set_state([
    'comments' => [
        '# Translator comment',
        '#. Extracted comment about forms',
        '#: src/main.php:42',
        '#, fuzzy',
        '#, c-format',
        '#| msgid "Old Form"',
        '# Additional context line 1',
        '# Additional context line 2',
    ],
    'msgid' => ['Forms'],
    'msgstr' => ['' => ['Űrlapok']],
]);

// Create a plural form item.
$item2Comments = new PoComment();
$item2Comments->setFlag('range', '1..100');
$item2 = new PoItem();
$item2->comments = $item2Comments->toItemValue();
$item2->msgid = ['1 minute'];
$item2->msgidPlural = ['@count minutes'];
$item2->msgstr = [
    0 => ['1 perc'],
    1 => ['@count perc'],
];

// Build file content.
$content = '';
foreach ([$item0, $item1, $item2] as $item) {
    $content .= (string) $item;
    $content .= "\n";
}
```

This generates:

```gettext
msgid ""
msgstr ""
"Project-Id-Version: MyProject (1.0.0)\n"
"Language: hu_HU\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n!=1);\n"

# Translator comment
#. Extracted comment about forms
#: src/main.php:42
#, fuzzy
#, c-format
#| msgid "Old Form"
# Additional context line 1
# Additional context line 2
msgid "Forms"
msgstr "Űrlapok"

#, range 1..100
msgid "1 minute"
msgid_plural "@count minutes"
msgstr[0] "1 perc"
msgstr[1] "@count perc"
```


[badge-circleci]: https://circleci.com/gh/Sweetchuck/po-parser/tree/2.x.svg?style=svg
[badge-codecov]: https://codecov.io/gh/Sweetchuck/po-parser/branch/2.x/graph/badge.svg?token=HSF16OGPyr
[link-circleci]: https://circleci.com/gh/Sweetchuck/po-parser/?branch=2.x
[link-codecov]: https://app.codecov.io/gh/Sweetchuck/po-parser/branch/2.x
