<?php

class TrustDelegatedExtension extends Parsedown
{
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['element']['text'];
        unset($Block['element']['element']['text']);

        // WARNING: There is almost always a better way of doing things!
        //
        // This behaviour is NOT needed in the demonstrated case.
        // Only use this if you are sure that the result being added into
        // rawHtml is safe.
        // (e.g. using an external parser with escaping capabilities).
        $Block['element']['element']['rawHtml'] = "<p>$text</p>";
        $Block['element']['element']['allowRawHtmlInSafeMode'] = true;

        return $Block;
    }
}
