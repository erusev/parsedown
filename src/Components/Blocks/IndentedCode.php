<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;

final class IndentedCode implements ContinuableBlock
{
    /** @var string */
    private $code;

    /**
     * @param string $code
     */
    private function __construct($code)
    {
        $this->code = $code;
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
        if (isset($Block) && $Block instanceof Paragraph && ! ($Context->precedingEmptyLines() > 0)) {
            return null;
        }

        if ($Context->line()->indent() < 4) {
            return null;
        }

        return new self($Context->line()->ltrimBodyUpto(4) . "\n");
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        if ($Context->line()->indent() < 4) {
            return null;
        }

        $newCode = $this->code;

        $offset = $Context->line()->indentOffset();

        if ($Context->precedingEmptyLines() > 0) {
            foreach (\explode("\n", $Context->precedingEmptyLinesText()) as $line) {
                $newCode .= (new Line($line, $offset))->ltrimBodyUpto(4) . "\n";
            }

            $newCode = \substr($newCode, 0, -1);
        }

        $newCode .= $Context->line()->ltrimBodyUpto(4) . "\n";

        return new self($newCode);
    }

    /** @return string */
    public function code()
    {
        return $this->code;
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return new Element(
            'pre',
            [],
            [new Element('code', [], [new Text($this->code())])]
        );
    }
}
