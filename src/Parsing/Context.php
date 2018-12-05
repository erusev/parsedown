<?php

namespace Erusev\Parsedown\Parsing;

final class Context
{
    /**
     * @var Line
     */
    private $Line;

    /**
     * @var int
     */
    private $previousEmptyLines;

    /**
     * @param Line $Line
     * @param int $previousEmptyLines
     */
    public function __construct($Line, $previousEmptyLines)
    {
        $this->Line = $Line;
        $this->previousEmptyLines = \max($previousEmptyLines, 0);
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
}
