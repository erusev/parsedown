<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Emphasis implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var string */
    private $text;

    /** @var 'em'|'strong' */
    private $type;

    const STRONG_REGEX = [
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
    ];

    const EM_REGEX = [
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];

    /**
     * @param string $text
     * @param 'em'|'strong' $type
     * @param int $width
     */
    public function __construct($text, $type, $width)
    {
        $this->text = $text;
        $this->type = $type;
        $this->width = $width;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (! isset($Excerpt->text()[1])) {
            return null;
        }

        $marker = $Excerpt->text()[0] === '*' ? '*' : '_';

        if ($Excerpt->text()[1] === $marker and \preg_match(self::STRONG_REGEX[$marker], $Excerpt->text(), $matches)) {
            $emphasis = 'strong';
        } elseif (\preg_match(self::EM_REGEX[$marker], $Excerpt->text(), $matches)) {
            $emphasis = 'em';
        } else {
            return null;
        }

        return new self($matches[1], $emphasis, \strlen($matches[0]));
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
                    $this->type,
                    [],
                    $State->applyTo($Parsedown->lineElements($this->text))
                );
            }
        );
    }
}
