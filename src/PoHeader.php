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
    /**
     * @return static
     */
    public static function createFromIterable(iterable $values)
    {
        $self = new static();
        foreach ($values as $key => $value) {
            $self->offsetSet($key, $value);
        }

        return $self;
    }

    /**
     * @return static
     */
    public static function createFromString(string $string)
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

    /**
     * @return static
     */
    public static function createFromItem(PoItem $poItem)
    {
        assert($poItem->msgid === ['']);

        return static::createFromString(implode("\n", $poItem->msgstr['']));
    }

    /**
     * @var array
     */
    protected array $items = [];

    //region ArrayAccess
    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$this->internalKey($offset)]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $internalKey = $this->internalKey($offset);

        return $this->items[$internalKey]['value'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
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
    public function offsetUnset($offset)
    {
        $internalKey = $this->internalKey($offset);
        unset($this->items[$internalKey]);
    }
    //endregion

    // region Countable
    public function count()
    {
        return count($this->items);
    }
    // endregion

    //region IteratorAggregate
    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->jsonSerialize());
    }
    //endregion

    //region JsonSerializable
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
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
    public function __toString()
    {
        $lines = [];
        foreach ($this->items as $item) {
            $lines[] = sprintf('%s: %s', $item['key'], $item['value']);
        }

        return implode(Utils::$eol, $lines) . Utils::$eol;
    }
    //endregion

    // region Common keys.
    public function getProjectIdVersion(): ?string
    {
        return $this->offsetGet('Project-Id-Version');
    }

    /**
     * @return $this
     */
    public function setProjectIdVersion(?string $value)
    {
        $this->offsetSet('Project-Id-Version', $value);

        return $this;
    }

    public function getReportMsgidBugsTo(): ?string
    {
        return $this->offsetGet('Report-Msgid-Bugs-To');
    }

    /**
     * @return $this
     */
    public function setReportMsgidBugsTo(?string $value)
    {
        $this->offsetSet('Report-Msgid-Bugs-To', $value);

        return $this;
    }

    public function getPotCreationDate(): ?string
    {
        return $this->offsetGet('POT-Creation-Date');
    }

    /**
     * @return $this
     */
    public function setPotCreationDate(?string $value)
    {
        $this->offsetSet('POT-Creation-Date', $value);

        return $this;
    }

    public function getPORevisionDate(): ?string
    {
        return $this->offsetGet('PO-Revision-Date');
    }

    /**
     * @return $this
     */
    public function setPoRevisionDate(?string $value)
    {
        $this->offsetSet('PO-Revision-Date', $value);

        return $this;
    }

    public function getLastTranslator(): ?string
    {
        return $this->offsetGet('Last-Translator');
    }

    /**
     * @return $this
     */
    public function setLastTranslator(?string $value)
    {
        $this->offsetSet('Last-Translator', $value);

        return $this;
    }

    public function getLanguageTeam(): ?string
    {
        return $this->offsetGet('Language-Team');
    }

    /**
     * @return $this
     */
    public function setLanguageTeam(?string $value)
    {
        $this->offsetSet('Language-Team', $value);

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->offsetGet('Language');
    }

    /**
     * @return $this
     */
    public function setLanguage(?string $value)
    {
        $this->offsetSet('Language', $value);

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->offsetGet('Content-Type');
    }

    /**
     * @return $this
     */
    public function setContentType(?string $value)
    {
        $this->offsetSet('Content-Type', $value);

        return $this;
    }

    public function getContentTransferEncoding(): ?string
    {
        return $this->offsetGet('Content-Transfer-Encoding');
    }

    /**
     * @return $this
     */
    public function setContentTransferEncoding(?string $value)
    {
        $this->offsetSet('Content-Transfer-Encoding', $value);

        return $this;
    }

    public function getMimeVersion(): ?string
    {
        return $this->offsetGet('Mime-Version');
    }

    /**
     * @return $this
     */
    public function setMimeVersion(?string $value)
    {
        $this->offsetSet('MIME-Version', $value);

        return $this;
    }

    public function getPluralForms(): ?string
    {
        return $this->offsetGet('Plural-Forms');
    }

    /**
     * @return $this
     */
    public function setPluralForms(?string $value)
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
