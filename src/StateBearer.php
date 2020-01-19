<?php

namespace Erusev\Parsedown;

interface StateBearer
{
    /**
     * @return State
     */
    public function state();
}
