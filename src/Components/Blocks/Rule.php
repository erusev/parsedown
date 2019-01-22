<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Rule implements Block
{
    use BlockAcquisition;

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
        if ($Context->line()->indent() > 3) {
            return null;
        }

        $marker = $Context->line()->text()[0];

        if (
            \substr_count($Context->line()->text(), $marker) >= 3
            and \chop($Context->line()->text(), " \t$marker") === ''
        ) {
            return new self;
        }

        return null;
    }

    /**
     * @return Element
     */
    public function stateRenderable(Parsedown $_)
    {
        return Element::selfClosing('hr', []);
    }
}
