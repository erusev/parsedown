<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Container;
use Erusev\Parsedown\Html\Renderables\Element;
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
     * @return Handler<Container>
     */
    public function stateRenderable(Parsedown $_)
    {
        return new Handler(
            /** @return Container */
            function (State $_) {
                $Renderables = [];
                $text = $this->text;

                $text = \preg_replace('/(?<![ \t])[ ]\n/', "$1\n", $text);

                while (\preg_match('/(?:[ ]*+[\\\]|[ ]{2,}+)\n/', $text, $matches, \PREG_OFFSET_CAPTURE)) {
                    $offset = \intval($matches[0][1]);
                    $before = \substr($text, 0, $offset);
                    $after = \substr($text, $offset + \strlen($matches[0][0]));
                    $Renderables[] = new Text($before);
                    $Renderables[] = Element::selfClosing('br', []);
                    $Renderables[] = new Text("\n");

                    $text = $after;
                }

                $Renderables[] = new Text($text);

                return new Container($Renderables);
            }
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->text);
    }
}
