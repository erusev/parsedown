<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\State;

trait CanonicalStateRenderable
{
    /**
     * @return Renderable
     */
    public function renderable(State $State)
    {
        return $this;
    }
}
