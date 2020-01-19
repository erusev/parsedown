<?php

namespace Erusev\Parsedown\Parsing;

final class Context
{
    /** @var Line */
    private $Line;

    /** @var int|null */
    private $precedingEmptyLines;

    /** @var string */
    private $precedingEmptyLinesText;

    /**
     * @param Line $Line
     * @param string $precedingEmptyLinesText
     */
    public function __construct($Line, $precedingEmptyLinesText)
    {
        $this->Line = $Line;
        $this->precedingEmptyLinesText = $precedingEmptyLinesText;
        $this->precedingEmptyLines = null;
    }

    /** @return Line */
    public function line()
    {
        return $this->Line;
    }

    /** @return int */
    public function precedingEmptyLines()
    {
        if (! isset($this->precedingEmptyLines)) {
            $this->precedingEmptyLines = \substr_count($this->precedingEmptyLinesText, "\n");
        }

        return $this->precedingEmptyLines;
    }

    /** @return string */
    public function precedingEmptyLinesText()
    {
        return $this->precedingEmptyLinesText;
    }
}
