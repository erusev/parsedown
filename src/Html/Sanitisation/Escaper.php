<?php

namespace Erusev\Parsedown\Html\Sanitisation;

final class Escaper
{
    /**
     * @param string $text
     * @return string
     */
    public static function htmlAttributeValue($text)
    {
        return self::escape($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public static function htmlElementValue($text)
    {
        return self::escape($text, true);
    }

    /**
     * @param string $text
     * @param bool $allowQuotes
     * @return string
     */
    private static function escape($text, $allowQuotes = false)
    {
        return \htmlspecialchars(
            $text,
            $allowQuotes ? \ENT_NOQUOTES : \ENT_QUOTES,
            'UTF-8'
        );
    }
}
