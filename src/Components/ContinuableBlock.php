<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\Parsing\Context;

interface ContinuableBlock extends Block
{
    /**
     * @param Context $Context
     * @return static|null
     */
    public function advance(Context $Context);
}
