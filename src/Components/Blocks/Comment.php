<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Comment implements ContinuableBlock
{
    use BlockAcquisition;

    /** @var string */
    private $html;

    /** @var bool */
    private $isClosed;

    /**
     * @param string $html
     * @param bool $isClosed
     */
    public function __construct($html, $isClosed)
    {
        $this->html = $html;
        $this->isClosed = $isClosed;
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
        if (\strpos($Context->line()->text(), '<!--') === 0) {
            return new self(
                $Context->line()->rawLine(),
                \strpos($Context->line()->text(), '-->') !== false
            );
        }

        return null;
    }

    /**
     * @param Context $Context
     * @return self|null
     */
    public function advance(Context $Context)
    {
        if ($this->isClosed) {
            return null;
        }

        return new self(
            $this->html . "\n" . $Context->line()->rawLine(),
            \strpos($Context->line()->text(), '-->') !== false
        );
    }

    /**
     * @return Handler<Text|RawHtml>
     */
    public function stateRenderable(Parsedown $Parsedown)
    {
        return new Handler(
            /** @return Text|RawHtml */
            function (State $State) {
                if ($State->get(SafeMode::class)->isEnabled()) {
                    return new Text($this->html);
                } else {
                    return new RawHtml($this->html);
                }
            }
        );
    }
}
