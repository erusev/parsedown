<?php

namespace Erusev\Parsedown;

interface StateBearer
{
    public function state(): State;
    /** @return static */
    public static function from(StateBearer $StateBearer);
}
