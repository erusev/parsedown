<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Paragraph implements ContinuableBlock
{
    use ContinuableBlockDefaultInterrupt, BlockAcquisition;

    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * @param Context $Context
     * @param Block|null $Block
     * @param State|null $State
     * @return static
     */
    public static function build(
        Context $Context,
        Block $Block = null,
        State $State = null
    ) {
        return new self($Context->line()->text());
    }

    /**
     * @param Context $Context
     * @return self|null
     */
    public function advance(Context $Context)
    {
        if ($this->interrupted) {
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
    public function stateRenderable(Parsedown $Parsedown)
    {
        return new Handler(
            /** @return Element */
            function (State $State) use ($Parsedown) {
                return new Element(
                    'p',
                    [],
                    $State->applyTo($Parsedown->lineElements($this->text))
                );
            }
        );
    }
}
