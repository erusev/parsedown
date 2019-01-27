<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\Escaper;

final class Text implements Renderable
{
    use CanonicalStateRenderable;

    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text = '')
    {
        $this->text = $text;
    }

    /** @return string */
    public function getStringBacking()
    {
        return $this->text;
    }

    /** @return string */
    public function getHtml()
    {
        return Escaper::htmlElementValueEscapingDoubleQuotes($this->text);
    }
}
