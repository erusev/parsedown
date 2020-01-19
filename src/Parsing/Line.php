<?php

namespace Erusev\Parsedown\Parsing;

final class Line
{
    const INDENT_STEP = 4;

    /** @var int */
    private $indent;

    /** @var int */
    private $indentOffset;

    /** @var string */
    private $rawLine;

    /** @var string */
    private $text;

    /**
     * @param string $line
     * @param int $indentOffset
     */
    public function __construct($line, $indentOffset = 0)
    {
        $this->rawLine = $line;
        $this->indentOffset = $indentOffset % self::INDENT_STEP;

        $lineWithoutTabs = self::indentTabsToSpaces($line, $indentOffset);

        $this->indent = \strspn($lineWithoutTabs, ' ');
        $this->text = \substr($lineWithoutTabs, $this->indent);
    }

    /** @return int */
    public function indentOffset()
    {
        return $this->indentOffset;
    }

    /** @return string */
    public function rawLine()
    {
        return $this->rawLine;
    }

    /**
     * @param int $fromPosition
     * @param int $indentOffset
     * @return int
     */
    public static function tabShortage($fromPosition, $indentOffset)
    {
        return self::INDENT_STEP - ($fromPosition + $indentOffset) % self::INDENT_STEP;
    }

    /**
     * @param string $text
     * @param int $indentOffset
     * @return string
     */
    private static function indentTabsToSpaces($text, $indentOffset = 0)
    {
        $rawIndentLen = \strspn($text, " \t");
        $indentString = \substr($text, 0, $rawIndentLen);
        $latterString = \substr($text, $rawIndentLen);

        while (($beforeTab = \strstr($indentString, "\t", true)) !== false) {
            $shortage = self::tabShortage(\mb_strlen($beforeTab, 'UTF-8'), $indentOffset);

            $indentString = $beforeTab
                . \str_repeat(' ', $shortage)
                . \substr($indentString, \strlen($beforeTab) + 1)
            ;
        }

        return $indentString . $latterString;
    }

    /**
     * @param int $pos
     * @return string
     */
    public function ltrimBodyUpto($pos)
    {
        if ($pos <= 0) {
            return $this->rawLine;
        }

        if ($pos >= $this->indent) {
            return \ltrim($this->rawLine, "\t ");
        }

        $rawIndentLen = \strspn($this->rawLine, " \t");
        $rawIndentString = \substr($this->rawLine, 0, $rawIndentLen);

        $effectiveIndent = 0;

        foreach (\str_split($rawIndentString) as $n => $char) {
            if ($char === "\t") {
                $shortage = self::tabShortage($effectiveIndent, $this->indentOffset);

                $effectiveIndent += $shortage;

                if ($effectiveIndent >= $pos) {
                    $overshoot = $effectiveIndent - $pos;

                    return \str_repeat(' ', $overshoot) . \substr($this->rawLine, $n + 1);
                }

                continue;
            } else {
                $effectiveIndent += 1;

                if ($effectiveIndent === $pos) {
                    return \substr($this->rawLine, $n + 1);
                }

                continue;
            }
        }

        return \ltrim($this->rawLine, "\t ");
    }

    /** @return int */
    public function indent()
    {
        return $this->indent;
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }
}
