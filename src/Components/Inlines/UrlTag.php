<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class UrlTag implements Inline
{
    use WidthTrait;

    /** @var string */
    private $url;

    /**
     * @param string $url
     * @param int $width
     */
    private function __construct($url, $width)
    {
        $this->url = $url;
        $this->width = $width;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (\preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt->text(), $matches)) {
            return new self($matches[1], \strlen($matches[0]));
        }

        return null;
    }

    /** @return string */
    public function url()
    {
        return $this->url;
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return new Element('a', ['href' => $this->url()], [new Text($this->url())]);
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->url);
    }
}
