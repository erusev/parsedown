<?php

namespace Erusev\Parsedown\Parsing;

final class Lines
{
    /** @var Context[] */
    private $contexts;

    /** @var bool */
    private $containsBlankLines;

    /** @var int */
    private $trailingBlankLines;

    /**
     * @param Context[] $contexts
     * @param int $trailingBlankLines
     */
    private function __construct($contexts, $trailingBlankLines)
    {
        $this->contexts = $contexts;
        $this->trailingBlankLines = $trailingBlankLines;

        $this->containsBlankLines = (
            ($trailingBlankLines > 0)
            || \array_reduce(
                $contexts,
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
    public static function empty()
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

        $contexts = [];
        $sequentialBreaks = 0;

        foreach (\explode("\n", $text) as $line) {
            if (\chop($line) === '') {
                $sequentialBreaks += 1;
                continue;
            }

            $contexts[] = new Context(
                new Line($line, $indentOffset),
                $sequentialBreaks
            );

            $sequentialBreaks = 0;
        }

        return new self($contexts, $sequentialBreaks);
    }

    /** @return bool */
    public function isEmpty()
    {
        return \count($this->contexts) === 0 && $this->trailingBlankLines === 0;
    }

    /** @return array */
    public function contexts()
    {
        return $this->contexts;
    }

    /** @return Context */
    public function last()
    {
        return $this->contexts[\count($this->contexts) -1];
    }

    /** @return bool */
    public function containsBlankLines(): bool
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

    /** @return Lines */
    public function appendingTextLines(string $text, int $indentOffset)
    {
        $Lines = clone($this);

        $NextLines = self::fromTextLines($text, $indentOffset);

        if (\count($NextLines->contexts) === 0) {
            $Lines->trailingBlankLines += $NextLines->trailingBlankLines;

            $Lines->containsBlankLines = $Lines->containsBlankLines
                || ($Lines->trailingBlankLines > 0)
            ;

            return $Lines;
        }

        $NextLines->contexts[0] = new Context(
            $NextLines->contexts[0]->line(),
            $NextLines->contexts[0]->previousEmptyLines() + $Lines->trailingBlankLines
        );

        $Lines->contexts = \array_merge($Lines->contexts, $NextLines->contexts);

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

        $Lines->contexts[] = $Context;

        return $Lines;
    }
}
