<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Code implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var string */
    private $text;

    /**
     * @param string $text
     * @param int $width
     */
    public function __construct($text, $width)
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
        $marker = $Excerpt->text()[0];

        if ($marker !== '`') {
            return null;
        }

        if (\preg_match(
            '/^(['.$marker.']++)[ ]*+(.+?)[ ]*+(?<!['.$marker.'])\1(?!'.$marker.')/s',
            $Excerpt->text(),
            $matches
        )) {
            $text = \preg_replace('/[ ]*+\n/', ' ', $matches[2]);

            return new self($text, \strlen($matches[0]));
        }
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return new Element('code', [], [new Text($this->text)]);
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->text);
    }
}
