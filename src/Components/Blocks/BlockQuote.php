<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;
use Erusev\Parsedown\State;

final class BlockQuote implements ContinuableBlock
{
    use BlockAcquisition;

    /** @var Lines */
    private $Lines;

    /**
     * @param Lines $Lines
     */
    public function __construct($Lines)
    {
        $this->Lines = $Lines;
    }

    /**
     * @param Context $Context
     * @param Block|null $Block
     * @param State|null $State
     * @return static|null
     */
    public static function build(
        Context $Context,
        Block $Block = null,
        State $State = null
    ) {
        if (\preg_match('/^(>[ \t]?+)(.*+)/', $Context->line()->text(), $matches)) {
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($matches[1]);

            $recoveredSpaces = 0;
            if (\strlen($matches[1]) === 2 && \substr($matches[1], 1, 1) === "\t") {
                $recoveredSpaces = Line::tabShortage(2, $Context->line()->indentOffset() + $Context->line()->indent());
            }

            return new self(
                Lines::fromTextLines(
                \str_repeat(' ', $recoveredSpaces) . $matches[2],
                $indentOffset
            )
            );
        }

        return null;
    }

    /**
     * @param Context $Context
     * @return self|null
     */
    public function advance(Context $Context)
    {
        if ($Context->previousEmptyLines() > 0) {
            return null;
        }

        if ($Context->line()->text()[0] === '>' && \preg_match('/^(>[ \t]?+)(.*+)/', $Context->line()->text(), $matches)) {
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($matches[1]);

            $recoveredSpaces = 0;
            if (\strlen($matches[1]) === 2 && \substr($matches[1], 1, 1) === "\t") {
                $recoveredSpaces = Line::tabShortage(2, $Context->line()->indentOffset() + $Context->line()->indent());
            }

            $Lines = $this->Lines->appendingTextLines(
                \str_repeat(' ', $recoveredSpaces) . $matches[2],
                $indentOffset
            );

            return new self($Lines);
        }

        if (! $Context->previousEmptyLines() > 0) {
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent();
            $Lines = $this->Lines->appendingTextLines($Context->line()->text(), $indentOffset);

            return new self($Lines);
        }

        return null;
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                return new Element(
                    'blockquote',
                    [],
                    $State->applyTo((new Parsedown($State))->lines($this->Lines))
                );
            }
        );
    }
}
