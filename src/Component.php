<?php

namespace Erusev\Parsedown;

use Erusev\Parsedown\AST\StateRenderable;

interface Component
{
    /**
     * @return StateRenderable
     */
    public function stateRenderable();
}
