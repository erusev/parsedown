<?php

namespace Erusev\Parsedown\Components\Blocks;

trait BlockAcquisition
{
    /** @var bool */
    private $acquired = false;

    /** @return bool */
    public function acquiredPrevious()
    {
        return $this->acquired;
    }
}
