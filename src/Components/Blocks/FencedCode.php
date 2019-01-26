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
    use BlockAcquisition;

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
    public function __construct($code, $infostring, $marker, $openerLength, $isComplete)
    {
        $this->code = $code;
        $this->infostring = $infostring;
        $this->marker = $marker;
        $this->openerLength = $openerLength;
        $this->isComplete = $isComplete;
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
        $marker = $Context->line()->text()[0];

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
     * @return self|null
     */
    public function advance(Context $Context)
    {
        if ($this->isComplete) {
            return null;
        }

        $newCode = $this->code;

        if ($Context->previousEmptyLines() > 0) {
            $newCode .= \str_repeat("\n", $Context->previousEmptyLines());
        }

        if (($len = \strspn($Context->line()->text(), $this->marker)) >= $this->openerLength
            && \chop(\substr($Context->line()->text(), $len), ' ') === ''
        ) {
            $newCode = \substr($newCode, 1);

            return new self($newCode, $this->infostring, $this->marker, $this->openerLength, true);
        }

        $newCode .= "\n" . $Context->line()->rawLine();

        return new self($newCode, $this->infostring, $this->marker, $this->openerLength, false);
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return new Element('pre', [], [new Element(
            'code',
            $this->infostring !== '' ? ['class' => "language-{$this->infostring}"] : [],
            [new Text($this->code)]
        )]);
    }
}
