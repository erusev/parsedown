<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Rule implements Block
{
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

        $marker = \substr($Context->line()->text(), 0, 1);

        if ($marker !== '*' && $marker !== '-' && $marker !== '_') {
            return null;
        }

        if (
            \substr_count($Context->line()->text(), $marker) >= 3
            && \chop($Context->line()->text(), " \t$marker") === ''
        ) {
            return new self;
        }

        return null;
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return Element::selfClosing('hr', []);
    }
}
