<?php

namespace Erusev\Parsedown\Parsing;

final class Context
{
    /** @var Line */
    private $Line;

    /** @var int */
    private $previousEmptyLines;

    /** @var string */
    private $previousEmptyLinesText;

    /**
     * @param Line $Line
     * @param string $previousEmptyLinesText
     */
    public function __construct($Line, $previousEmptyLinesText)
    {
        $this->Line = $Line;
        $this->previousEmptyLinesText = $previousEmptyLinesText;
        $this->previousEmptyLines = \substr_count($previousEmptyLinesText, "\n");
    }

    /** @return Line */
    public function line()
    {
        return $this->Line;
    }

    /** @return int */
    public function previousEmptyLines()
    {
        return $this->previousEmptyLines;
    }

    /** @return string */
    public function previousEmptyLinesText()
    {
        return $this->previousEmptyLinesText;
    }
}
