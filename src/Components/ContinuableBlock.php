<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

interface ContinuableBlock extends Block
{
    /**
     * @param Context $Context
     * @param State $State
     * @return static|null
     */
    public function advance(Context $Context, State $State);
}
