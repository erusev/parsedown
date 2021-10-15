<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;
use Erusev\Parsedown\State;

final class BlockQuote implements ContinuableBlock
{
    /** @var Lines */
    private $Lines;

    /**
     * @param Lines $Lines
     */
    private function __construct($Lines)
    {
        $this->Lines = $Lines;
    }

    /**
     * @param Context $Context
     * @param State $State
     * @param Block|null $Block
     * @return static|null
     */
    public static function build(
        Context $Context,
        State $State,
        Block $Block = null
    ) {
        if (\preg_match('/^(>[ \t]?+)(.*+)/', $Context->line()->text(), $matches)) {
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($matches[1]);

            $recoveredSpaces = 0;
            if (\strlen($matches[1]) === 2 && \substr($matches[1], 1, 1) === "\t") {
                $recoveredSpaces = Line::tabShortage(0, $indentOffset -1) -1;
            }

            return new self(Lines::fromTextLines(
                \str_repeat(' ', $recoveredSpaces) . $matches[2],
                $indentOffset
            ));
        }

        return null;
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        if ($Context->precedingEmptyLines() > 0) {
            return null;
        }

        if (\preg_match('/^(>[ \t]?+)(.*+)/', $Context->line()->text(), $matches)) {
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($matches[1]);

            $recoveredSpaces = 0;
            if (\strlen($matches[1]) === 2 && \substr($matches[1], 1, 1) === "\t") {
                $recoveredSpaces = Line::tabShortage(0, $indentOffset -1) -1;
            }

            $Lines = $this->Lines->appendingTextLines(
                \str_repeat(' ', $recoveredSpaces) . $matches[2],
                $indentOffset
            );

            return new self($Lines);
        }

        if (!($Context->precedingEmptyLines() > 0)) {
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent();
            $Lines = $this->Lines->appendingTextLines($Context->line()->text(), $indentOffset);

            return new self($Lines);
        }

        return null;
    }

    /**
     * @return array{Block[], State}
     */
    public function contents(State $State)
    {
        return Parsedown::blocks($this->Lines, $State);
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                list($Blocks, $State) = $this->contents($State);

                $StateRenderables = Parsedown::stateRenderablesFrom($Blocks);

                $Renderables = $State->applyTo($StateRenderables);
                $Renderables[] = new Text("\n");

                return new Element('blockquote', [], $Renderables);
            }
        );
    }
}
