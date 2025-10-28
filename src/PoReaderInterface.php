<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

/**
 * Gettext PO/POT file parser.
 *
 * @link https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html
 *
 * @template TKey of int
 * @template TValue of \Sweetchuck\PoParser\PoItem
 *
 * @extends \Iterator<TKey, TValue>
 * @extends \SeekableIterator<TKey, TValue>
 */
interface PoReaderInterface extends \Iterator, \SeekableIterator, \Stringable, \JsonSerializable
{

    /**
     * @phpstan-param sweetchuck-po-reader-reader-state-import $values
     */
    public static function __set_state(array $values): static;

    /**
     * The next read will be from the given key.
     *
     * @param int<0, max> $key
     */
    public function setKey(int $key): static;

    /**
     * Key is the POItem index, value is the \ftell() result.
     *
     * @return array<int<0, max>, int<0, max>>
     */
    public function getPositions(): array;

    /**
     * @param array<int<0, max>, int<0, max>> $positions
     */
    public function setPositions(array $positions): static;

    /**
     * @return null|resource
     */
    public function getFileHandler();

    /**
     * @param null|resource $fileHandler
     */
    public function setFileHandler($fileHandler): static;
}
