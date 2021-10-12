<?php

namespace Erusev\Parsedown;

interface StateBearer
{
    public function state(): State;
}
