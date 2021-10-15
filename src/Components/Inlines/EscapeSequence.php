<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class EscapeSequence implements Inline
{
    use WidthTrait;

    const SPECIALS = '!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';

    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    private function __construct($text)
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
        $char = \substr($Excerpt->text(), 1, 1);

        if ($char !== '' && \strpbrk($char, self::SPECIALS) !== false) {
            return new self($char);
        }

        return null;
    }

    /** @return string */
    public function char()
    {
        return $this->text;
    }

    /**
     * @return Text
     */
    public function stateRenderable()
    {
        return new Text($this->char());
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->char());
    }
}
