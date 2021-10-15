<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;
use Erusev\Parsedown\State;

final class TList implements ContinuableBlock
{
    /** @var Lines[] */
    private $Lis;

    /** @var int|null */
    private $listStart;

    /** @var bool */
    private $isLoose;

    /** @var int */
    private $indent;

    /** @var 'ul'|'ol' */
    private $type;

    /** @var string */
    private $marker;

    /** @var int */
    private $afterMarkerSpaces;

    /** @var string */
    private $markerType;

    /** @var string */
    private $markerTypeRegex;

    /**
     * @param Lines[] $Lis
     * @param int|null $listStart
     * @param bool $isLoose
     * @param int $indent
     * @param 'ul'|'ol' $type
     * @param string $marker
     * @param int $afterMarkerSpaces
     * @param string $markerType
     * @param string $markerTypeRegex
     */
    private function __construct(
        $Lis,
        $listStart,
        $isLoose,
        $indent,
        $type,
        $marker,
        $afterMarkerSpaces,
        $markerType,
        $markerTypeRegex
    ) {
        $this->Lis = $Lis;
        $this->listStart = $listStart;
        $this->isLoose = $isLoose;
        $this->indent = $indent;
        $this->type = $type;
        $this->marker = $marker;
        $this->afterMarkerSpaces = $afterMarkerSpaces;
        $this->markerType = $markerType;
        $this->markerTypeRegex = $markerTypeRegex;
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
        list($type, $pattern) = (
            \substr($Context->line()->text(), 0, 1) <= '-'
            ? ['ul', '[*+-]']
            : ['ol', '[0-9]{1,9}+[.\)]']
        );

        if (\preg_match(
            '/^('.$pattern.')([\t ]++.*|$)/',
            $Context->line()->text(),
            $matches
        )) {
            $marker = $matches[1];

            $preAfterMarkerSpacesIndentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($marker);

            $LineWithMarkerIndent = new Line(isset($matches[2]) ? $matches[2] : '', $preAfterMarkerSpacesIndentOffset);
            $indentAfterMarker = $LineWithMarkerIndent->indent();

            if ($indentAfterMarker > 4) {
                $perceivedIndent = $indentAfterMarker -1;
                $afterMarkerSpaces = 1;
            } else {
                $perceivedIndent = 0;
                $afterMarkerSpaces = $indentAfterMarker;
            }

            $indentOffset = $preAfterMarkerSpacesIndentOffset + $afterMarkerSpaces;
            $text = \str_repeat(' ', $perceivedIndent) . $LineWithMarkerIndent->text();

            $markerType = (
                $type === 'ul'
                ? $marker
                : \substr($marker, -1)
            );

            $markerTypeRegex = \preg_quote($markerType, '/');

            /** @var int|null */
            $listStart = null;

            if ($type === 'ol') {
                /** @psalm-suppress PossiblyFalseArgument */
                $listStart = \intval(\strstr($matches[1], $markerType, true) ?: '0');

                if (
                    $listStart !== 1
                    && isset($Block)
                    && $Block instanceof Paragraph
                    && ! ($Context->precedingEmptyLines() > 0)
                ) {
                    return null;
                }
            }

            return new self(
                [!empty($text) ? Lines::fromTextLines($text, $indentOffset) : Lines::none()],
                $listStart,
                false,
                $Context->line()->indent(),
                $type,
                $marker,
                $afterMarkerSpaces,
                $markerType,
                $markerTypeRegex
            );
        }
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        if ($Context->precedingEmptyLines() > 0 && \end($this->Lis)->isEmpty()) {
            return null;
        }

        $newlines = \str_repeat("\n", $Context->precedingEmptyLines());

        $requiredIndent = $this->indent + \strlen($this->marker) + $this->afterMarkerSpaces;
        $isLoose = $this->isLoose;
        $indent = $Context->line()->indent();

        $Lis = $this->Lis;

        if ($this->type === 'ol') {
            $regex = '/^([0-9]++'.$this->markerTypeRegex.')([\t ]++.*|$)/';
        } else {
            $regex = '/^('.$this->markerTypeRegex.')([\t ]++.*|$)/';
        }

        if ($Context->line()->indent() < $requiredIndent
            && \preg_match($regex, $Context->line()->text(), $matches)
        ) {
            if ($Context->precedingEmptyLines() > 0) {
                $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingBlankLines(1);

                $isLoose = true;
            }

            $marker = $matches[1];

            $preAfterMarkerSpacesIndentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($marker);

            $LineWithMarkerIndent = new Line(isset($matches[2]) ? $matches[2] : '', $preAfterMarkerSpacesIndentOffset);
            $indentAfterMarker = $LineWithMarkerIndent->indent();

            if ($indentAfterMarker > 4) {
                $perceivedIndent = $indentAfterMarker -1;
                $afterMarkerSpaces = 1;
            } else {
                $perceivedIndent = 0;
                $afterMarkerSpaces = $indentAfterMarker;
            }

            $indentOffset = $preAfterMarkerSpacesIndentOffset + $afterMarkerSpaces;
            $text = \str_repeat(' ', $perceivedIndent) . $LineWithMarkerIndent->text();

            $Lis[] = Lines::fromTextLines($newlines . $text, $indentOffset);

            return new self(
                $Lis,
                $this->listStart,
                $isLoose,
                $indent,
                $this->type,
                $marker,
                $afterMarkerSpaces,
                $this->markerType,
                $this->markerTypeRegex
            );
        } elseif ($Context->line()->indent() < $requiredIndent && self::build($Context, $State) !== null) {
            return null;
        }

        if ($Context->line()->indent() >= $requiredIndent) {
            if ($Context->precedingEmptyLines() > 0) {
                $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingBlankLines($Context->precedingEmptyLines());

                $isLoose = true;
            }

            $text = $Context->line()->ltrimBodyUpto($requiredIndent);
            $indentOffset = $Context->line()->indentOffset() + \min($requiredIndent, $Context->line()->indent());

            $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingTextLines($text, $indentOffset);

            return new self(
                $Lis,
                $this->listStart,
                $isLoose,
                $this->indent,
                $this->type,
                $this->marker,
                $this->afterMarkerSpaces,
                $this->markerType,
                $this->markerTypeRegex
            );
        }

        if (! ($Context->precedingEmptyLines() > 0)) {
            $text = $Context->line()->ltrimBodyUpto($requiredIndent);

            $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingTextLines(
                $newlines . \str_repeat(' ', $Context->line()->indent()) . $text,
                $Context->line()->indentOffset() + \min($requiredIndent, $Context->line()->indent())
            );

            return new self(
                $Lis,
                $this->listStart,
                $isLoose,
                $this->indent,
                $this->type,
                $this->marker,
                $this->afterMarkerSpaces,
                $this->markerType,
                $this->markerTypeRegex
            );
        }

        return null;
    }

    /**
     * @return array{Block[], State}[]
     */
    public function items(State $State)
    {
        return \array_map(
            /** @return array{Block[], State} */
            function (Lines $Lines) use ($State) {
                return Parsedown::blocks($Lines, $State);
            },
            $this->Lis
        );
    }

    /** @return 'ol'|'ul' */
    public function type()
    {
        return $this->type;
    }

    /** @return int|null */
    public function listStart()
    {
        return $this->listStart;
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                $listStart = $this->listStart();

                return new Element(
                    $this->type(),
                    (
                        isset($listStart) && $listStart !== 1
                        ? ['start' => \strval($listStart)]
                        : []
                    ),
                    \array_map(
                        /**
                         * @param array{Block[], State} $Item
                         * @return Element
                         * */
                        function ($Item) {
                            list($Blocks, $State) = $Item;

                            $StateRenderables = Parsedown::stateRenderablesFrom($Blocks);
                            $Renderables = $State->applyTo($StateRenderables);

                            if (! $this->isLoose
                                && isset($Renderables[0])
                                && $Renderables[0] instanceof Element
                                && $Renderables[0]->name() === 'p'
                            ) {
                                $Contents = $Renderables[0]->contents();
                                unset($Renderables[0]);
                                $Renderables = \array_merge($Contents ?: [], $Renderables);
                            }

                            return new Element('li', [], $Renderables);
                        },
                        $this->items($State)
                    )
                );
            }
        );
    }
}
