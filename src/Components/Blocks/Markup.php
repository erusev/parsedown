<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Markup implements ContinuableBlock
{
    use ContinuableBlockDefaultInterrupt, BlockAcquisition;

    const REGEX_HTML_ATTRIBUTE = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    /** @var string */
    private $html;

    /**
     * @param string $html
     */
    public function __construct($html)
    {
        $this->html = $html;
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
        if (\preg_match('/^<[\/]?+(\w*)(?:[ ]*+'.self::REGEX_HTML_ATTRIBUTE.')*+[ ]*+(\/)?>/', $Context->line()->text(), $matches)) {
            $element = \strtolower($matches[1]);

            if (\array_key_exists($element, Element::TEXT_LEVEL_ELEMENTS)) {
                return null;
            }

            return new self($Context->line()->text());
        }
    }

    /**
     * @param Context $Context
     * @return self|null
     */
    public function continue(Context $Context)
    {
        if ($this->interrupted) {
            return null;
        }

        $html = $this->html . "\n" . $Context->line()->rawLine();

        return new self($html);
    }

    /**
     * @return Handler<Element|RawHtml>
     */
    public function stateRenderable(Parsedown $Parsedown)
    {
        return new Handler(
            /** @return Element|RawHtml */
            function (State $State) {
                $SafeMode = $State->getOrDefault(SafeMode::class);

                if ($SafeMode->enabled()) {
                    return new Element('p', [], [new Text($this->html)]);
                } else {
                    return new RawHtml($this->html);
                }
            }
        );
    }
}
