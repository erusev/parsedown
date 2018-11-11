<?php declare(strict_types=1);

namespace Erusev\Parsedown\Html\Sanitisation;

final class Escaper
{
    public static function htmlAttributeValue(string $text) : string
    {
        return self::escape($text);
    }

    public static function htmlElementValue(string $text) : string
    {
        return self::escape($text, true);
    }

    private static function escape(
        string $text,
        bool $allowQuotes = false
    ) : string {
        return \htmlentities(
            $text,
            $allowQuotes ? \ENT_NOQUOTES : \ENT_QUOTES,
            'UTF-8'
        );
    }
}
