<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class FencedCode implements ContinuableBlock
{
    /** @var string */
    private $code;

    /** @var string */
    private $infostring;

    /** @var string */
    private $marker;

    /** @var int */
    private $openerLength;

    /** @var bool */
    private $isComplete;

    /**
     * @param string $code
     * @param string $infostring
     * @param string $marker
     * @param int $openerLength
     * @param bool $isComplete
     */
    private function __construct($code, $infostring, $marker, $openerLength, $isComplete)
    {
        $this->code = $code;
        $this->infostring = $infostring;
        $this->marker = $marker;
        $this->openerLength = $openerLength;
        $this->isComplete = $isComplete;
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
        $marker = \substr($Context->line()->text(), 0, 1);

        if ($marker !== '`' && $marker !== '~') {
            return null;
        }

        $openerLength = \strspn($Context->line()->text(), $marker);

        if ($openerLength < 3) {
            return null;
        }

        $infostring = \trim(\substr($Context->line()->text(), $openerLength), "\t ");

        if (\strpos($infostring, '`') !== false) {
            return null;
        }

        return new self('', $infostring, $marker, $openerLength, false);
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        if ($this->isComplete) {
            return null;
        }

        $newCode = $this->code;

        $newCode .= $Context->previousEmptyLinesText();

        if (($len = \strspn($Context->line()->text(), $this->marker)) >= $this->openerLength
            && \chop(\substr($Context->line()->text(), $len), ' ') === ''
        ) {
            return new self($newCode, $this->infostring, $this->marker, $this->openerLength, true);
        }

        $newCode .= $Context->line()->rawLine() . "\n";

        return new self($newCode, $this->infostring, $this->marker, $this->openerLength, false);
    }

    /** @return string */
    public function infostring()
    {
        return $this->infostring;
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
        $infostring = $this->infostring();

        return new Element('pre', [], [new Element(
            'code',
            $infostring !== '' ? ['class' => "language-{$infostring}"] : [],
            [new Text($this->code())]
        )]);
    }
}
