<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class SpecialCharacter implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var string */
    private $charCodeHtml;

    /**
     * @param string $charCodeHtml
     */
    public function __construct($charCodeHtml)
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
        if (\substr($Excerpt->text(), 1, 1) !== ' ' && \strpos($Excerpt->text(), ';') !== false
            && \preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt->text(), $matches)
        ) {
            return new self($matches[1]);
        }

        return null;
    }

    /**
     * @return RawHtml
     */
    public function stateRenderable()
    {
        return new RawHtml(
            '&' . (new Text($this->charCodeHtml))->getHtml() . ';'
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text('&'.$this->charCodeHtml.';');
    }
}
