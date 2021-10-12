<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\Escaper;
use Erusev\Parsedown\Html\TransformableRenderable;

final class Text implements TransformableRenderable
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

    /**
     * @param \Closure(string):Renderable $Transform
     * @return Renderable
     */
    public function transformingContent(\Closure $Transform): Renderable
    {
        return $Transform($this->text);
    }
}
