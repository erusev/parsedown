<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Strikethrough implements Inline
{
    use WidthTrait;

    /** @var string */
    private $text;

    /**
     * @param string $text
     * @param int $width
     */
    private function __construct($text, $width)
    {
        $this->text = $text;
        $this->width = $width;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        $text = $Excerpt->text();

        if (\preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $text, $matches)) {
            return new self($matches[1], \strlen($matches[0]));
        }

        return null;
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                return new Element(
                    'del',
                    [],
                    $State->applyTo(Parsedown::line($this->text(), $State))
                );
            }
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->text());
    }
}
