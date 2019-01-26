<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class SetextHeader implements Block
{
    use BlockAcquisition;

    /** @var string */
    private $text;

    /** @var 1|2 */
    private $level;

    /**
     * @param string $text
     * @param 1|2 $level
     */
    public function __construct($text, $level)
    {
        $this->text = $text;
        $this->level = $level;
        $this->acquired = true;
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
        if (! isset($Block) || ! $Block instanceof Paragraph || $Context->previousEmptyLines() > 0) {
            return null;
        }

        if ($Context->line()->indent() < 4 && \chop(\chop($Context->line()->text(), " \t"), $Context->line()->text()[0]) === '') {
            $level = $Context->line()->text()[0] === '=' ? 1 : 2;

            return new self($Block->text(), $level);
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
                    'h' . \strval($this->level),
                    [],
                    $State->applyTo((new Parsedown($State))->line($this->text))
                );
            }
        );
    }
}
