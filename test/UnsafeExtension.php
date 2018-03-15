<?php

class UnsafeExtension extends Parsedown
{
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['text']['text'];
        unset($Block['element']['text']['text']);

        $Block['element']['text']['unsafeHtml'] = "<p>$text</p>";

        return $Block;
    }
}
