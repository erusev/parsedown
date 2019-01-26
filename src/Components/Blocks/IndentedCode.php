<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class IndentedCode implements ContinuableBlock
{
    use BlockAcquisition;

    /** @var string */
    private $code;

    /**
     * @param string $code
     */
    public function __construct($code)
    {
        $this->code = $code;
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
        if (isset($Block) && $Block instanceof Paragraph && ! $Context->previousEmptyLines() > 0) {
            return null;
        }

        if ($Context->line()->indent() < 4) {
            return null;
        }

        return new self($Context->line()->ltrimBodyUpto(4));
    }

    /**
     * @param Context $Context
     * @return self|null
     */
    public function advance(Context $Context)
    {
        if ($Context->line()->indent() < 4) {
            return null;
        }

        $newCode = $this->code;

        if ($Context->previousEmptyLines() > 0) {
            $newCode .= \str_repeat("\n", $Context->previousEmptyLines());
        }

        return new self($newCode . "\n" . $Context->line()->ltrimBodyUpto(4));
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return new Element(
            'pre',
            [],
            [new Element('code', [], [new Text($this->code)])]
        );
    }
}
