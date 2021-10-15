<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\Components\BacktrackingInline;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class HardBreak implements BacktrackingInline
{
    use WidthTrait;

    /** @var int */
    private $position;

    /**
     * @param int $width
     * @param int $position
     */
    private function __construct($width, $position)
    {
        $this->width = $width;
        $this->position = $position;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        $marker = \substr($Excerpt->text(), 0, 1);

        if ($marker !== "\n") {
            return null;
        }

        $context = $Excerpt->context();
        $offset = $Excerpt->offset();

        if (\substr($context, $offset -1, 1) === '\\') {
            $contentLen = $offset -1;

            return new self($offset - $contentLen, $contentLen);
        }

        if (\substr($context, $offset -2, 2) === '  ') {
            $trimTrailingWhitespace = \rtrim(\substr($context, 0, $offset));
            $contentLen = \strlen($trimTrailingWhitespace);

            return new self($offset - $contentLen, $contentLen);
        }

        return null;
    }

    /**
     * Return an integer to declare that the inline should be treated as if it
     * started from that position in the excerpt given to static::build.
     * Return null to use the excerpt offset value.
     * @return int|null
     * */
    public function modifyStartPositionTo()
    {
        return $this->position;
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return Element::selfClosing('br', []);
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text("\n");
    }
}
