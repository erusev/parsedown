<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Markup implements Inline
{
    use WidthTrait;

    const HTML_ATT_REGEX = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    /** @var string */
    private $html;

    /**
     * @param string $html
     */
    private function __construct($html)
    {
        $this->html = $html;
        $this->width = \strlen($html);
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        $secondChar = \substr($Excerpt->text(), 1, 1);

        if ($secondChar === '/' && \preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt->text(), $matches)) {
            return new self($matches[0]);
        }

        if ($secondChar === '!' && \preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt->text(), $matches)) {
            return new self($matches[0]);
        }

        if ($secondChar !== ' ' && \preg_match('/^<\w[\w-]*+(?:[ ]*+'.self::HTML_ATT_REGEX.')*+[ ]*+\/?>/s', $Excerpt->text(), $matches)) {
            return new self($matches[0]);
        }
    }

    /** @return string */
    public function html()
    {
        return $this->html;
    }

    /**
     * @return Handler<Text|RawHtml>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Text|RawHtml */
            function (State $State) {
                if ($State->get(SafeMode::class)->isEnabled()) {
                    return new Text($this->html());
                } else {
                    return new RawHtml($this->html());
                }
            }
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->html());
    }
}
