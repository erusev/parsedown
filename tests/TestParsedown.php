<?php

declare(strict_types=1);

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Parsedown;

class TestParsedown extends Parsedown
{
    public function getTextLevelElements()
    {
        return $this->textLevelElements;
    }
}
