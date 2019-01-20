<?php

namespace Erusev\Parsedown\Parsing;

final class Lines
{
    /** @var Context[] */
    private $Contexts;

    /** @var bool */
    private $containsBlankLines;

    /** @var int */
    private $trailingBlankLines;

    /**
     * @param Context[] $Contexts
     * @param int $trailingBlankLines
     */
    private function __construct($Contexts, $trailingBlankLines)
    {
        $this->Contexts = $Contexts;
        $this->trailingBlankLines = $trailingBlankLines;

        $this->containsBlankLines = (
            ($trailingBlankLines > 0)
            || \array_reduce(
                $Contexts,
                /**
                 * @param bool $blankFound
                 * @param Context $Context
                 * @return bool
                 */
                function ($blankFound, $Context) {
                    return $blankFound || ($Context->previousEmptyLines() > 0);
                },
                false
            )
        );
    }

    /** @return self */
    public static function none()
    {
        return new self([], 0);
    }

    /**
     * @param string $text
     * @param int $indentOffset
     * @return self
     */
    public static function fromTextLines($text, $indentOffset)
    {
        # standardize line breaks
        $text = \str_replace(["\r\n", "\r"], "\n", $text);

        $Contexts = [];
        $sequentialBreaks = 0;

        foreach (\explode("\n", $text) as $line) {
            if (\chop($line) === '') {
                $sequentialBreaks += 1;
                continue;
            }

            $Contexts[] = new Context(
                new Line($line, $indentOffset),
                $sequentialBreaks
            );

            $sequentialBreaks = 0;
        }

        return new self($Contexts, $sequentialBreaks);
    }

    /** @return bool */
    public function isEmpty()
    {
        return \count($this->Contexts) === 0 && $this->trailingBlankLines === 0;
    }

    /** @return Context[] */
    public function Contexts()
    {
        return $this->Contexts;
    }

    /** @return Context */
    public function last()
    {
        return $this->Contexts[\count($this->Contexts) -1];
    }

    /** @return bool */
    public function containsBlankLines()
    {
        return $this->containsBlankLines;
    }

    /** @return int */
    public function trailingBlankLines()
    {
        return $this->trailingBlankLines;
    }

    /**
     * @param int $count
     * @return self
     */
    public function appendingBlankLines($count = 1)
    {
        if ($count < 0) {
            $count = 0;
        }

        $Lines = clone($this);
        $Lines->trailingBlankLines += $count;
        $Lines->containsBlankLines = $Lines->containsBlankLines || ($count > 0);

        return $Lines;
    }

    /**
     * @param string $text
     * @param int $indentOffset
     * @return Lines
     */
    public function appendingTextLines($text, $indentOffset)
    {
        $Lines = clone($this);

        $NextLines = self::fromTextLines($text, $indentOffset);

        if (\count($NextLines->Contexts) === 0) {
            $Lines->trailingBlankLines += $NextLines->trailingBlankLines;

            $Lines->containsBlankLines = $Lines->containsBlankLines
                || ($Lines->trailingBlankLines > 0)
            ;

            return $Lines;
        }

        $NextLines->Contexts[0] = new Context(
            $NextLines->Contexts[0]->line(),
            $NextLines->Contexts[0]->previousEmptyLines() + $Lines->trailingBlankLines
        );

        $Lines->Contexts = \array_merge($Lines->Contexts, $NextLines->Contexts);

        $Lines->trailingBlankLines = $NextLines->trailingBlankLines;

        $Lines->containsBlankLines = $Lines->containsBlankLines
            || $NextLines->containsBlankLines
        ;

        return $Lines;
    }

    /** @return Lines */
    public function appendingContext(Context $Context)
    {
        $Lines = clone($this);

        $Context = new Context(
            $Context->line(),
            $Context->previousEmptyLines() + $Lines->trailingBlankLines
        );

        if ($Context->previousEmptyLines() > 0) {
            $Lines->containsBlankLines = true;
        }

        $Lines->trailingBlankLines = 0;

        $Lines->Contexts[] = $Context;

        return $Lines;
    }
}
