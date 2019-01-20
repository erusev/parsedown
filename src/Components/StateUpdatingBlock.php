<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\State;

interface StateUpdatingBlock extends Block
{
    /** @return State */
    public function latestState();
}
