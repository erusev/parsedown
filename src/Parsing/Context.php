<?php

namespace Erusev\Parsedown\Parsing;

final class Context
{
    /** @var Line */
    private $Line;

    /** @var int|null */
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
        $this->previousEmptyLines = null;
    }

    /** @return Line */
    public function line()
    {
        return $this->Line;
    }

    /** @return int */
    public function previousEmptyLines()
    {
        if (! isset($this->previousEmptyLines)) {
            $this->previousEmptyLines = \substr_count($this->previousEmptyLinesText, "\n");
        }

        return $this->previousEmptyLines;
    }

    /** @return string */
    public function previousEmptyLinesText()
    {
        return $this->previousEmptyLinesText;
    }
}
