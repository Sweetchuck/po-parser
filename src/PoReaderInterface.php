<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

/**
 * Gettext PO/POT file parser.
 *
 * @link https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html
 */
interface PoReaderInterface extends \Iterator, \SeekableIterator, \Stringable, \JsonSerializable
{

    //public function setKey(int $key);
    //
    //public function getPositions(): array;
    //
    //public function setPositions(array $positions);
    //
    ///**
    // * @return null|resource
    // */
    //public function getFileHandler();
    //
    ///**
    // * @param null|resource $fileHandler
    // *
    // * @return $this
    // */
    //public function setFileHandler($fileHandler);
}
