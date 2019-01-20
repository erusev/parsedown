<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;

final class RawHtml implements Renderable
{
    use CanonicalStateRenderable;

    /** @var string */
    private $html;

    /**
     * @param string $html
     */
    public function __construct($html = '')
    {
        $this->html = $html;
    }

    /** @return string */
    public function getHtml()
    {
        return $this->html;
    }
}
