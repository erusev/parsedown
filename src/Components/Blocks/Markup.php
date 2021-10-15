<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Markup implements ContinuableBlock
{
    private const REGEX_HTML_ATTRIBUTE = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    private const BLOCK_ELEMENTS = [
        'address' => true,
        'article' => true,
        'aside' => true,
        'base' => true,
        'basefont' => true,
        'blockquote' => true,
        'body' => true,
        'caption' => true,
        'center' => true,
        'col' => true,
        'colgroup' => true,
        'dd' => true,
        'details' => true,
        'dialog' => true,
        'dir' => true,
        'div' => true,
        'dl' => true,
        'dt' => true,
        'fieldset' => true,
        'figcaption' => true,
        'figure' => true,
        'footer' => true,
        'form' => true,
        'frame' => true,
        'frameset' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'head' => true,
        'header' => true,
        'hr' => true,
        'html' => true,
        'iframe' => true,
        'legend' => true,
        'li' => true,
        'link' => true,
        'main' => true,
        'menu' => true,
        'menuitem' => true,
        'nav' => true,
        'noframes' => true,
        'ol' => true,
        'optgroup' => true,
        'option' => true,
        'p' => true,
        'param' => true,
        'section' => true,
        'source' => true,
        'summary' => true,
        'table' => true,
        'tbody' => true,
        'td' => true,
        'tfoot' => true,
        'th' => true,
        'thead' => true,
        'title' => true,
        'tr' => true,
        'track' => true,
        'ul' => true,
    ];

    private const SIMPLE_CONTAINS_END_CONDITIONS = [
        2 => '-->',
        3 => '?>',
        4 => '>',
        5 => ']]>',
    ];

    private const SPECIAL_HTML_BLOCK_TAGS = [
        'script' => true,
        'style' => true,
        'pre' => true,
    ];

    /** @var string */
    private $html;

    /** @var 1|2|3|4|5|6|7 */
    private $type;

    /** @var bool */
    private $closed;

    /**
     * @param string $html
     * @param 1|2|3|4|5|6|7 $type
     * @param bool $closed
     */
    private function __construct($html, $type, $closed = false)
    {
        $this->html = $html;
        $this->type = $type;
        $this->closed = $closed;
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
        $text = $Context->line()->text();
        $rawLine = $Context->line()->rawLine();

        if (\preg_match('/^<(?:script|pre|style)(?:\s++|>|$)/i', $text)) {
            return new self($rawLine, 1, self::closes12345TypeMarkup(1, $text));
        }

        if (\substr($text, 0, 4) === '<!--') {
            return new self($rawLine, 2, self::closes12345TypeMarkup(2, $text));
        }

        if (\substr($text, 0, 2) === '<?') {
            return new self($rawLine, 3, self::closes12345TypeMarkup(3, $text));
        }

        if (\preg_match('/^<![A-Z]/', $text)) {
            return new self($rawLine, 4, self::closes12345TypeMarkup(4, $text));
        }

        if (\substr($text, 0, 9) === '<![CDATA[') {
            return new self($rawLine, 5, self::closes12345TypeMarkup(5, $text));
        }

        if (\preg_match('/^<([\/]?+)(\w++)(.*+)$/', $text, $matches)) {
            $isClosing = ($matches[1] === '/');
            $element = \strtolower($matches[2]);
            $tail = $matches[3];

            if (\array_key_exists($element, self::BLOCK_ELEMENTS)
                && \preg_match('/^(?:\s|$|>|\/)/', $tail)
            ) {
                return new self($rawLine, 6);
            }

            if (
                ! $isClosing && \preg_match(
                    '/^(?:[ ]*+'.self::REGEX_HTML_ATTRIBUTE.')*(?:[ ]*+)[\/]?+[>](.*+)$/',
                    $tail,
                    $matches
                ) || $isClosing && \preg_match(
                    '/^(?:[ ]*+)[\/]?+[>](.*+)$/',
                    $tail,
                    $matches
                )
            ) {
                $tail = $matches[1];

                if (! \array_key_exists($element, self::SPECIAL_HTML_BLOCK_TAGS)
                    && ! (isset($Block) && $Block instanceof Paragraph && $Context->precedingEmptyLines() < 1)
                    && \preg_match('/^\s*+$/', $tail)
                ) {
                    return new self($rawLine, 7);
                }
            }
        }

        return null;
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        $closed = $this->closed;
        $type = $this->type;

        if ($closed) {
            return null;
        }

        if (($type === 6 || $type === 7) && $Context->precedingEmptyLines() > 0) {
            return null;
        }

        if ($type === 1 || $type === 2 || $type === 3 || $type === 4 || $type === 5) {
            $closed = self::closes12345TypeMarkup($type, $Context->line()->text());
        }

        $html = $this->html . \str_repeat("\n", $Context->precedingEmptyLines() + 1);
        $html .= $Context->line()->rawLine();

        return new self($html, $type, $closed);
    }

    /**
     * @param 1|2|3|4|5 $type
     * @param string $text
     * @return bool
     */
    private static function closes12345TypeMarkup($type, $text)
    {
        if ($type === 1) {
            if (\preg_match('/<\/(?:script|pre|style)>/i', $text)) {
                return true;
            }
        } elseif (\stripos($text, self::SIMPLE_CONTAINS_END_CONDITIONS[$type]) !== false) {
            return true;
        }

        return false;
    }

    /** @return string */
    public function html()
    {
        return $this->html;
    }

    /**
     * @return Handler<Element|RawHtml>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element|RawHtml */
            function (State $State) {
                if ($State->get(SafeMode::class)->isEnabled()) {
                    return new Element('p', [], [new Text($this->html())]);
                } else {
                    return new RawHtml($this->html());
                }
            }
        );
    }
}
