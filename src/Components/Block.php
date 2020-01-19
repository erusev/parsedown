<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\Component;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

interface Block extends Component
{
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
    );
}
