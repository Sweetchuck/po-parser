<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

/**
 * Case in-sensitive key-value storage.
 *
 * @link https://www.gnu.org/software/gettext/manual/gettext.html#Filling-in-the-Header-Entry
 */
class PoHeader implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Stringable
{
    public static function createFromIterable(iterable $values): static
    {
        $self = new static();
        foreach ($values as $key => $value) {
            $self->offsetSet($key, $value);
        }

        return $self;
    }

    public static function createFromString(string $string): static
    {
        $poHeader = new PoHeader();
        if ($string === '') {
            return $poHeader;
        }

        $lines = explode(Utils::$eol, $string);
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $parts = explode(': ', $line, 2) + [1 => ''];
            $poHeader->offsetSet($parts[0], $parts[1]);
        }

        return $poHeader;
    }

    public static function createFromItem(PoItem $poItem): static
    {
        assert($poItem->msgid === ['']);

        return static::createFromString(implode("\n", $poItem->msgstr['']));
    }

    protected array $items = [];

    //region ArrayAccess
    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$this->internalKey($offset)]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ?string
    {
        $internalKey = $this->internalKey($offset);

        return $this->items[$internalKey]['value'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        if ($value === null) {
            $this->offsetUnset($offset);

            return;
        }

        $internalKey = $this->internalKey($offset);
        if (!isset($this->items[$internalKey])) {
            $this->items[$internalKey]['key'] = $offset;
        }

        $this->items[$internalKey]['value'] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $internalKey = $this->internalKey($offset);
        unset($this->items[$internalKey]);
    }
    //endregion

    // region Countable
    public function count(): int
    {
        return count($this->items);
    }
    // endregion

    //region IteratorAggregate
    /**
     * {@inheritdoc}
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->jsonSerialize());
    }
    //endregion

    //region JsonSerializable
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $values = [];
        foreach ($this->items as $item) {
            $values[$item['key']] = $item['value'];
        }

        return $values;
    }
    //endregion

    //region Stringable
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $lines = [];
        foreach ($this->items as $item) {
            $lines[] = sprintf('%s: %s', $item['key'], $item['value']);
        }

        return implode(Utils::$eol, $lines) . Utils::$eol;
    }
    //endregion

    //region Common keys.
    public function getProjectIdVersion(): ?string
    {
        return $this->offsetGet('Project-Id-Version');
    }

    public function setProjectIdVersion(?string $value): static
    {
        $this->offsetSet('Project-Id-Version', $value);

        return $this;
    }

    public function getReportMsgidBugsTo(): ?string
    {
        return $this->offsetGet('Report-Msgid-Bugs-To');
    }

    public function setReportMsgidBugsTo(?string $value): static
    {
        $this->offsetSet('Report-Msgid-Bugs-To', $value);

        return $this;
    }

    public function getPotCreationDate(): ?string
    {
        return $this->offsetGet('POT-Creation-Date');
    }

    public function setPotCreationDate(?string $value): static
    {
        $this->offsetSet('POT-Creation-Date', $value);

        return $this;
    }

    public function getPORevisionDate(): ?string
    {
        return $this->offsetGet('PO-Revision-Date');
    }

    public function setPoRevisionDate(?string $value): static
    {
        $this->offsetSet('PO-Revision-Date', $value);

        return $this;
    }

    public function getLastTranslator(): ?string
    {
        return $this->offsetGet('Last-Translator');
    }

    public function setLastTranslator(?string $value): static
    {
        $this->offsetSet('Last-Translator', $value);

        return $this;
    }

    public function getLanguageTeam(): ?string
    {
        return $this->offsetGet('Language-Team');
    }

    public function setLanguageTeam(?string $value): static
    {
        $this->offsetSet('Language-Team', $value);

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->offsetGet('Language');
    }

    public function setLanguage(?string $value): static
    {
        $this->offsetSet('Language', $value);

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->offsetGet('Content-Type');
    }

    public function setContentType(?string $value): static
    {
        $this->offsetSet('Content-Type', $value);

        return $this;
    }

    public function getContentTransferEncoding(): ?string
    {
        return $this->offsetGet('Content-Transfer-Encoding');
    }

    public function setContentTransferEncoding(?string $value): static
    {
        $this->offsetSet('Content-Transfer-Encoding', $value);

        return $this;
    }

    public function getMimeVersion(): ?string
    {
        return $this->offsetGet('Mime-Version');
    }

    public function setMimeVersion(?string $value): static
    {
        $this->offsetSet('MIME-Version', $value);

        return $this;
    }

    public function getPluralForms(): ?string
    {
        return $this->offsetGet('Plural-Forms');
    }

    public function setPluralForms(?string $value): static
    {
        $this->offsetSet('Plural-Forms', $value);

        return $this;
    }
    //endregion

    protected function internalKey(string $key): string
    {
        return mb_strtolower($key);
    }
}
