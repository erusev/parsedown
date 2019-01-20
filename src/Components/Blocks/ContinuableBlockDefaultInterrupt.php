<?php

namespace Erusev\Parsedown\Components\Blocks;

trait ContinuableBlockDefaultInterrupt
{
    /** @var bool */
    private $interrupted = false;

    /**
     * @param bool $isInterrupted
     */
    public function interrupted($isInterrupted)
    {
        $New = clone($this);
        $New->interrupted = $isInterrupted;

        return $New;
    }

    /**
     * @return bool
     */
    public function isInterrupted()
    {
        return $this->interrupted;
    }
}
