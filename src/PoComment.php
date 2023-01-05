<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

class PoComment implements \JsonSerializable, \Stringable
{
    public static function __set_state($values): static
    {
        $self = new static();
        if (array_key_exists('items', $values)) {
            $self->items = $values['items'];
        }

        if (array_key_exists('counters', $values)) {
            $self->counters = $values['counters'];
        }

        return $self;
    }

    protected array $items = [];

    /**
     * @var int[]
     */
    protected array $counters = [
        'translator' => 0,
        'previous' => 0,
        'extracted' => 0,
        'reference' => 0,
    ];

    protected string $lastId = '';

    public function getLastId(): string
    {
        return $this->lastId;
    }

    public function __toString(): string
    {
        $content = '';
        foreach ($this->items as $item) {
            switch ($item['type']) {
                case 'flag':
                    $prefix = '#, ';
                    $current = $item['flag'] . ($item['comment'] === '' ? '' : " {$item['comment']}");
                    break;

                case 'previous':
                    $prefix = '#| ';
                    $current = explode("\n", (string) $item['poItem']);
                    break;

                case 'extracted':
                    $prefix = '#. ';
                    $current = explode("\n", $item['comment']);
                    break;

                case 'reference':
                    $prefix = '#: ';
                    $current = implode(' ', $item['references']);
                    break;

                default:
                    $prefix = '#  ';
                    $current = $item['comment'];
                    break;
            }

            $content .= is_array($current) ?
                rtrim(preg_replace(
                    '/^/usm',
                    $prefix,
                    implode("\n", $current),
                ))
                : "$prefix$current";
            $content = rtrim($content) . "\n";
        }

        return $content;
    }

    public function jsonSerialize(): array
    {
        // @todo What about the ::$lastId?
        return [
            'items' => $this->items,
            'counters' => $this->counters,
        ];
    }

    public function toItemValue(): array
    {
        return explode("\n", rtrim((string) $this));
    }

    public function setTranslator(string $comment, int $weight = 0, ?string $id = null): static
    {
        $type = 'translator';
        $this->lastId = $id ?? sprintf("$type:%d", $this->counters[$type]++);

        $this->items[$this->lastId] = [
            'weight' => $weight,
            'type' => $type,
            'comment' => $comment,
        ];

        return $this;
    }

    public function setFlag(string $name, string $comment = '', int $weight = 0): static
    {
        $type = 'flag';

        $this->items["$type:$name"] = [
            'weight' => $weight,
            'type' => $type,
            'flag' => $name,
            'comment' => $comment,
        ];

        return $this;
    }

    public function setExtracted(string $comment, int $weight = 0, ?string $id = null): static
    {
        $type = 'extracted';
        $this->lastId = $id ?? sprintf("$type:%d", $this->counters[$type]++);

        $this->items[$this->lastId] = [
            'weight' => $weight,
            'type' => $type,
            'comment' => $comment,
        ];

        return $this;
    }

    public function setReference(array $references, int $weight = 0, ?string $id = null): static
    {
        $type = 'reference';
        $this->lastId = $id ?? sprintf("$type:%d", $this->counters[$type]++);

        $this->items[$this->lastId] = [
            'weight' => $weight,
            'type' => $type,
            'references' => $references,
        ];

        return $this;
    }

    public function setPrevious(PoItem $poItem, int $weight = 0, ?string $id = null): static
    {
        $type = 'previous';
        $this->lastId = $id ?? sprintf("$type:%d", $this->counters[$type]++);

        $this->items[$this->lastId] = [
            'weight' => $weight,
            'type' => $type,
            'poItem' => $poItem,
        ];

        return $this;
    }

    public function delete(string $id): static
    {
        unset($this->items[$id]);

        return $this;
    }
}
