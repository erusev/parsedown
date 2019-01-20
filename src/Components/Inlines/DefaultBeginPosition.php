<?php

namespace Erusev\Parsedown\Components\Inlines;

trait DefaultBeginPosition
{
    /** @return int|null */
    public function modifyStartPositionTo()
    {
        return null;
    }
}
