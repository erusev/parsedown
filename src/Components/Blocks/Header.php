<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Header implements Block
{
    use BlockAcquisition;

    /** @var string */
    private $text;

    /** @var 1|2|3|4|5|6 */
    private $level;

    /**
     * @param string $text
     * @param 1|2|3|4|5|6 $level
     */
    public function __construct($text, $level)
    {
        $this->text = $text;
        $this->level = $level;
    }

    /**
     * @param Context $Context
     * @param Block|null $Block
     * @param State|null $State
     * @return static|null
     */
    public static function build(
        Context $Context,
        Block $Block = null,
        State $State = null
    ) {
        $State = $State ?: new State;

        $level = \strspn($Context->line()->text(), '#');

        if ($level > 6 || $level < 1) {
            return null;
        }

        /** @var 1|2|3|4|5|6 $level */

        $text = \trim($Context->line()->text(), '#');

        $StrictMode = $State->getOrDefault(StrictMode::class);

        if ($StrictMode->enabled() && isset($text[0]) and $text[0] !== ' ') {
            return null;
        }

        $text = \trim($text, ' ');

        return new self($text, $level);
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
                    'h' . \strval($this->level),
                    [],
                    $State->applyTo($Parsedown->line($this->text))
                );
            }
        );
    }
}
