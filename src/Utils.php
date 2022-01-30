<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

class Utils
{
    public static string $eol = "\n";

    public static int $lineLength = 68;

    public static function escapeString(string $string): string
    {
        return addcslashes($string, "\0..\37\"");
    }

    /**
     * @param string[] $lines
     *
     * @return string
     */
    public static function linesToPo(array $lines): string
    {
        if (count($lines) === 1) {
            return sprintf(
                '"%s"' . static::$eol,
                static::escapeString((string) reset($lines)),
            );
        }

        $result = '""' . static::$eol;
        foreach ($lines as $line) {
            $result .= '"' . static::escapeString($line) . '"' . static::$eol;
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public static function explode(string $string, int $lineLength = 0): array
    {
        $hardLines = explode(
            "\n",
            preg_replace_callback(
                '/(\\\+)n/u',
                function (array $matches) {
                    return mb_strlen($matches[1]) % 2 === 0 ?
                        $matches[0]
                        : mb_substr($matches[1], 2) . "\n";
                },
                $string,
            ),
        );

        $lines = [];
        foreach ($hardLines as $hardLine) {
            $softLines = wordwrap(
                $hardLine,
                $lineLength ?: static::$lineLength,
                " \n",
            );
            $lines = array_merge($lines, explode("\n", $softLines . '\n'));
        }

        $lastKey = array_key_last($lines);
        $lines[$lastKey] = mb_substr($lines[$lastKey], 0, -2);
        if (count($lines) > 1 && $lines[$lastKey] === '') {
            array_pop($lines);
        }

        return $lines;
    }
}
