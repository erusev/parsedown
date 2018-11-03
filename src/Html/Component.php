<?php declare(strict_types=1);

namespace Erusev\Parsedown\Html;

interface Component
{
    public function getHtml(): string;
}
