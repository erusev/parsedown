<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Paragraph implements ContinuableBlock
{
    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    private function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * @param Context $Context
     * @param State $State
     * @param Block|null $Block
     * @return static
     */
    public static function build(
        Context $Context,
        State $State,
        Block $Block = null
    ) {
        return new self($Context->line()->text());
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        if ($Context->precedingEmptyLines() > 0) {
            return null;
        }

        return new self($this->text . "\n" . $Context->line()->text());
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
                    'p',
                    [],
                    $State->applyTo(Parsedown::line(\trim($this->text()), $State))
                );
            }
        );
    }
}
