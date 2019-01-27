<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class EscapeSequence implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    const SPECIALS = '!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';

    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = $text;
        $this->width = 2;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (isset($Excerpt->text()[1]) && \strpbrk($c = $Excerpt->text()[1], self::SPECIALS) !== false) {
            return new self($c);
        }

        return null;
    }

    /**
     * @return Text
     */
    public function stateRenderable()
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
