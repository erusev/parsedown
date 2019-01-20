<?php

namespace Erusev\Parsedown\Components\Inlines;

trait WidthTrait
{
    /** @var int */
    private $width;

    /** @return int */
    public function width()
    {
        return $this->width;
    }
}
