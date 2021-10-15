<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Rule implements Block
{
    private function __construct()
    {
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
