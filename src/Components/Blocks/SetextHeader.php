<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\AcquisitioningBlock;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class SetextHeader implements AcquisitioningBlock
{
    /** @var string */
    private $text;

    /** @var 1|2 */
    private $level;

    /**
     * @param string $text
     * @param 1|2 $level
     */
    private function __construct($text, $level)
    {
        $this->text = $text;
        $this->level = $level;
    }

    /**
     * @param Context $Context
     * @param State $State
     * @param Block|null $Block
     * @return static|null
     */
    public static function build(
        Context $Context,
        State $State,
        Block $Block = null
    ) {
        if (! isset($Block) || ! $Block instanceof Paragraph || $Context->previousEmptyLines() > 0) {
            return null;
        }

        $marker = \substr($Context->line()->text(), 0, 1);

        if ($marker !== '=' && $marker !== '-') {
            return null;
        }

        if (
            $Context->line()->indent() < 4
            && \chop(\chop($Context->line()->text(), " \t"), $marker) === ''
        ) {
            $level = ($marker === '=' ? 1 : 2);

            return new self(\trim($Block->text()), $level);
        }

        return null;
    }

    /** @return bool */
    public function acquiredPrevious()
    {
        return true;
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }

    /** @return 1|2 */
    public function level()
    {
        return $this->level;
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
                    'h' . \strval($this->level()),
                    [],
                    $State->applyTo(Parsedown::line($this->text(), $State))
                );
            }
        );
    }
}
