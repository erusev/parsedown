<?php

namespace Erusev\Parsedown\Components;

interface AcquisitioningBlock extends Block
{
    /**
     * Return true if the block was built encompassing the previous block
     * $Block given to static::build, return false otherwise.
     * @return bool
     */
    public function acquiredPrevious();
}
