<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\Component;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

interface Block extends Component
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
    );
}
