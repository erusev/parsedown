<?php declare(strict_types=1);

namespace Erusev\Parsedown\Html\Components;

use Erusev\Parsedown\Html\Component;
use Erusev\Parsedown\Html\Sanitisation\CharacterFilter;
use Erusev\Parsedown\Html\Sanitisation\Escaper;

final class Text implements Component
{
    /** @var string */
    private $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function getHtml(): string
    {
        return Escaper::htmlElementValue($text);
    }
}
