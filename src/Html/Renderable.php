<?php

namespace Erusev\Parsedown\Html;

use Erusev\Parsedown\AST\StateRenderable;

interface Renderable extends StateRenderable
{
    /** @return string */
    public function getHtml();
}
