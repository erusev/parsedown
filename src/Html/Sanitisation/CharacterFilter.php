<?php declare(strict_types=1);

namespace Erusev\Parsedown\Html\Sanitisation;

final class CharacterFilter
{
    public static function htmlAttributeName(string $text) : string
    {
        /**
         * https://www.w3.org/TR/html/syntax.html#name
         *
         * Attribute names must consist of one or more characters other than
         * the space characters, U+0000 NULL, U+0022 QUOTATION MARK ("),
         * U+0027 APOSTROPHE ('), U+003E GREATER-THAN SIGN (>),
         * U+002F SOLIDUS (/), and U+003D EQUALS SIGN (=) characters,
         * the control characters, and any characters that are not defined by
         * Unicode.
         */
        return \preg_replace(
            '/(?:[[:space:]\0"\'>\/=[:cntrl:]]|[^\pC\pL\pM\pN\pP\pS\pZ])++/iu',
            '',
            $text
        );
    }

    public static function htmlElementName(string $text) : string
    {
        /**
         * https://www.w3.org/TR/html/syntax.html#tag-name
         *
         * HTML elements all have names that only use alphanumeric
         * ASCII characters.
         */
        return \preg_replace('/[^[:alnum:]]/', '', $text);
    }
}
