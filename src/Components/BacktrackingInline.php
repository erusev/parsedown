<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\Parsing\Excerpt;

interface BacktrackingInline extends Inline
{
    /**
     * Return an integer to declare that the inline should be treated as if it
     * started from that position in the excerpt given to static::build.
     * Return null to use the excerpt offset value.
     * @return int|null
     * */
    public function modifyStartPositionTo();
}
