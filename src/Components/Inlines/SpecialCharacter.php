<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class SpecialCharacter implements Inline
{
    use WidthTrait;

    /** @var string */
    private $charCodeHtml;

    /**
     * @param string $charCodeHtml
     */
    private function __construct($charCodeHtml)
    {
        $this->charCodeHtml = $charCodeHtml;
        $this->width = \strlen($charCodeHtml) + 2;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (\preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt->text(), $matches)) {
            return new self($matches[1]);
        }

        return null;
    }

    /** @return string */
    public function charCode()
    {
        return $this->charCodeHtml;
    }

    /**
     * @return RawHtml
     */
    public function stateRenderable()
    {
        return new RawHtml(
            '&' . (new Text($this->charCode()))->getHtml() . ';'
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text('&'.$this->charCode().';');
    }
}
