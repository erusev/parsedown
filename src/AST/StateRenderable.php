<?php

namespace Erusev\Parsedown\AST;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\State;

interface StateRenderable
{
    /**
     * @param State $State
     * @return Renderable
     */
    public function renderable(State $State);
}
