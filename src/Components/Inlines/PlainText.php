<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class PlainText implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = $text;
        $this->width = \strlen($text);
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static
     */
    public static function build(Excerpt $Excerpt, State $State = null)
    {
        return new self($Excerpt->text());
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }

    /**
     * @return Text
     */
    public function stateRenderable(Parsedown $_)
    {
        return new Text($this->text);
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->text);
    }
}
