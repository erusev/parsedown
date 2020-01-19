<?php

namespace Erusev\Parsedown\Parsing;

final class Lines
{
    /** @var Context[] */
    private $Contexts;

    /** @var bool */
    private $containsBlankLines;

    /** @var string */
    private $trailingBlankLinesText;

    /** @var int */
    private $trailingBlankLines;

    /**
     * @param Context[] $Contexts
     * @param string $trailingBlankLinesText
     */
    private function __construct($Contexts, $trailingBlankLinesText)
    {
        $this->Contexts = $Contexts;
        $this->trailingBlankLinesText = $trailingBlankLinesText;
        $this->trailingBlankLines = \substr_count($trailingBlankLinesText, "\n");

        $containsBlankLines = $this->trailingBlankLines > 0;

        if (! $containsBlankLines) {
            foreach ($Contexts as $Context) {
                if ($Context->precedingEmptyLines() > 0) {
                    $containsBlankLines = true;
                    break;
                }
            }
        }

        $this->containsBlankLines = $containsBlankLines;
    }

    /** @return self */
    public static function none()
    {
        return new self([], '');
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
        $sequentialEmptyLines = '';

        foreach (\explode("\n", $text) as $line) {
            if (\chop($line) === '') {
                $sequentialEmptyLines .= $line . "\n";
                continue;
            }

            $Contexts[] = new Context(
                new Line($line, $indentOffset),
                $sequentialEmptyLines
            );

            $sequentialEmptyLines = '';
        }

        return new self($Contexts, $sequentialEmptyLines);
    }

    /** @return bool */
    public function isEmpty()
    {
        return ! $this->containsBlankLines && \count($this->Contexts) === 0;
    }

    /** @return Context[] */
    public function contexts()
    {
        return $this->Contexts;
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
        $Lines->trailingBlankLinesText .= \str_repeat("\n", $count);
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
            $Lines->trailingBlankLinesText .= $NextLines->trailingBlankLinesText;

            $Lines->containsBlankLines = true;

            return $Lines;
        }

        $NextLines->Contexts[0] = new Context(
            $NextLines->Contexts[0]->line(),
            $NextLines->Contexts[0]->precedingEmptyLinesText() . $Lines->trailingBlankLinesText
        );

        $Lines->Contexts = \array_merge($Lines->Contexts, $NextLines->Contexts);

        $Lines->trailingBlankLines = $NextLines->trailingBlankLines;
        $Lines->trailingBlankLinesText = $NextLines->trailingBlankLinesText;

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
            $Context->precedingEmptyLinesText() . $Lines->trailingBlankLinesText
        );

        if ($Context->precedingEmptyLines() > 0) {
            $Lines->containsBlankLines = true;
        }

        $Lines->trailingBlankLines = 0;
        $Lines->trailingBlankLinesText = '';

        $Lines->Contexts[] = $Context;

        return $Lines;
    }
}
