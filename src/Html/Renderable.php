<?php declare(strict_types=1);

namespace Erusev\Parsedown\Html;

interface Renderable
{
    public function getHtml(): string;
}
