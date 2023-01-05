<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

/**
 * Gettext PO/POT file parser.
 *
 * WARNING: The internal state of this class is not strictly protected.
 * That means, an inconsistent state can be caused by using one of the following
 * methods:
 * - ::__set_state()
 * - ::setFileHandler()
 * - ::setPositions()
 * - ::setKey()
 * So be careful, and don't do anything stupid.
 * For example set a file handler, which is not at the beginning of the file,
 * without proper ::key and ::positions values. It still works though, but can
 * cause side effects.
 * In the other hand, this openness gives flexibility.
 */
class PoReader implements PoReaderInterface
{

    public static function __set_state($values): static
    {
        $self = new static();

        if (array_key_exists('positions', $values)) {
            $self->setPositions($values['positions']);
        }

        if (array_key_exists('isAllReaded', $values)) {
            $self->isAllReaded = $values['isAllReaded'];
        }

        if (array_key_exists('fileHandler', $values)) {
            $self->setFileHandler($values['fileHandler']);
        }

        if (array_key_exists('key', $values)) {
            $self->seek($values['key']);
        } else {
            $self->readNext();
        }

        return $self;
    }

    protected string $eol = "\n";

    /**
     * Current PoItem index.
     *
     * @var int
     */
    protected int $key = -1;

    public function setKey(int $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Current PoItem.
     *
     * @var null|\Sweetchuck\PoParser\PoItem
     */
    protected ?PoItem $current = null;

    protected ?string $currentLine = null;

    protected bool $isAllReaded = false;

    // region positions
    /**
     * Key is the POItem index, value is the ftell result.
     *
     * @var array
     */
    protected array $positions = [];

    public function getPositions(): array
    {
        return $this->positions;
    }

    public function setPositions(array $positions): static
    {
        $this->positions = $positions;

        return $this;
    }
    // endregion

    //region fileHandler
    /**
     * @var null|resource
     */
    protected $fileHandler = null;

    /**
     * @return null|resource
     */
    public function getFileHandler()
    {
        return $this->fileHandler;
    }

    /**
     * @param null|resource $fileHandler
     */
    public function setFileHandler($fileHandler): static
    {
        assert(is_resource($fileHandler));
        assert(get_resource_type($fileHandler) === 'stream');
        assert(stream_get_meta_data($fileHandler)['seekable'] === true);

        $this->fileHandler = $fileHandler;

        return $this;
    }
    //endregion

    //region jsonSerialize
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key(),
            'positions' => $this->getPositions(),
            'isAllReaded' => $this->isAllReaded,
        ];
    }
    //endregion

    //region Stringable
    public function __toString(): string
    {
        $items = [];
        foreach ($this as $poItem) {
            $items[] = (string) $poItem;
        }

        return implode($this->eol, $items);
    }
    //endregion

    // region Iterator
    public function current(): ?PoItem
    {
        return $this->current;
    }

    public function next(): void
    {
        $this->readNext();
    }

    public function key(): int
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return $this->current !== null;
    }

    public function rewind(): void
    {
        fseek($this->fileHandler, $this->positions[0] ?? 0);
        $this->key = -1;
        $this->currentLine = null;
        $this->readNext();
    }
    // endregion

    // region SeekableIterator
    public function seek($offset): void
    {
        if ($offset < 0) {
            throw new \OutOfBoundsException(
                "offset has to be >= 0; current $offset",
                1,
            );
        }

        if (isset($this->positions[$offset])) {
            fseek($this->fileHandler, $this->positions[$offset]);
            $this->key = $offset - 1;
            $this->readNext();

            return;
        }

        if (!$this->positions) {
            $this->key = -1;
            fseek($this->fileHandler, 0);
        } else {
            $this->key = count($this->positions) - 2;
            fseek($this->fileHandler, $this->positions[$this->key] ?? 0);
        }

        $this->readNext();
        while ($this->key < $offset && !$this->isAllReaded && $this->valid()) {
            $this->readNext();
        }

        if (!$this->valid()) {
            throw new \OutOfBoundsException(
                "maximum offset: {$this->key}; requested offset: $offset",
                1,
            );
        }
    }
    // endregion

    protected function readNext(): static
    {
        $this->key++;

        $currentLineLength = $this->currentLine === null ? 0 : strlen($this->currentLine);
        if (!isset($this->positions[$this->key])) {
            $this->positions[$this->key] = max(
                ftell($this->fileHandler) - $currentLineLength - 1,
                0,
            );
        }
        $this->isAllReaded = $this->isAllReaded || feof($this->fileHandler);

        if ($this->currentLine === null) {
            $this->readNextLine();
        }
        $state = [
            'comments' => $this->readComments(),
            'msgctxt' => $this->readMsgCtxt(),
            'msgid' => $this->readMsgid(),
            'msgidPlural' => $this->readMsgidPlural(),
            'msgstr' => $this->readMsgstr(),
        ];

        if ($state['msgid']) {
            $this->current = PoItem::__set_state($state);
            return $this;
        }

        $this->key--;
        $this->current = null;

        return $this;
    }

    protected function readComments(): array
    {
        $lines = [];
        while ($this->currentLine !== null
            && str_starts_with($this->currentLine, '#')
        ) {
            $lines[] = $this->currentLine;
            $this->readNextLine();
        }

        return $lines;
    }

    protected function readMsgCtxt(): array
    {
        return $this->readKeyword('msgctxt');
    }

    protected function readMsgid(): array
    {
        return $this->readKeyword('msgid');
    }

    protected function readMsgidPlural(): array
    {
        return $this->readKeyword('msgid_plural');
    }

    protected function readMsgstr(): array
    {
        $keyword = 'msgstr';
        $item = $this->readKeyword($keyword);
        if ($item) {
            return ['' => $item];
        }

        $items = [];
        $index = 0;
        $keyword = "msgstr[$index]";
        while ($item = $this->readKeyword($keyword)) {
            $items[$index] = $item;
            $index++;
            $keyword = "msgstr[$index]";
        }

        return $items;
    }

    protected function readKeyword(string $keyword): array
    {
        $lines = [];
        $removePattern = '/^' . preg_quote($keyword) . ' /';
        while ($this->isKeywordLine($keyword)) {
            $line = trim($this->currentLine);
            if ($line === '') {
                $this->readNextLine();

                continue;
            }

            if (trim($this->currentLine) !== '') {
                $lines[] = stripcslashes(mb_substr(
                    preg_replace($removePattern, '', $this->currentLine),
                    1,
                    -1,
                ));
            }

            $this->readNextLine();
        }

        if (count($lines) > 1) {
            array_shift($lines);
        }

        return $lines;
    }

    protected function readNextLine(): static
    {
        if (feof($this->fileHandler)) {
            $this->currentLine = null;

            return $this;
        }

        $line = fgets($this->fileHandler);
        if ($line === false) {
            $this->currentLine = null;

            return $this;
        }

        $this->currentLine = rtrim($line, "\r\n");

        return $this;
    }

    protected function isKeywordLine(string $keyword): bool
    {
        return $this->currentLine !== null
            && (
                trim($this->currentLine) === ''
                || str_starts_with($this->currentLine, '"')
                || str_starts_with($this->currentLine, "$keyword ")
            );
    }
}
