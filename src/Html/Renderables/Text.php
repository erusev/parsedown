<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\Escaper;

final class Text implements Renderable
{
    /** @var string */
    private $text;

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public function getHtml(): string
    {
        return Escaper::htmlElementValue($this->text);
    }
}
