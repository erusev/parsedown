<?php

class TestParsedown extends Parsedown
{
    public function getTextLevelElements()
    {
        return $this->textLevelElements;
    }
}
