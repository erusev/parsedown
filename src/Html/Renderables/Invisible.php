<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;

final class Invisible implements Renderable
{
    use CanonicalStateRenderable;

    public function __construct()
    {
    }

    /** @return string */
    public function getHtml()
    {
        return '';
    }
}
