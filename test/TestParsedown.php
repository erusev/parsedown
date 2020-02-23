<?php

class TestParsedown extends \Parsedown\Parsedown
{
    public function getTextLevelElements()
    {
        return $this->textLevelElements;
    }
}
