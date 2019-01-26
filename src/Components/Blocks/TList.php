<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
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
    use BlockAcquisition;

    /** @var Lines[] */
    private $Lis;

    /** @var string|null */
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
     * @param string|null $listStart
     * @param bool $isLoose
     * @param int $indent
     * @param 'ul'|'ol' $type
     * @param string $marker
     * @param int $afterMarkerSpaces
     * @param string $markerType
     * @param string $markerTypeRegex
     */
    public function __construct(
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
     * @param Block|null $Block
     * @param State|null $State
     * @return static|null
     */
    public static function build(
        Context $Context,
        Block $Block = null,
        State $State = null
    ) {
        list($type, $pattern) = (
            $Context->line()->text()[0] <= '-'
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

            /** @var string|null */
            $listStart = null;

            if ($type === 'ol') {
                /** @psalm-suppress PossiblyFalseArgument */
                $listStart = \ltrim(\strstr($matches[1], $markerType, true), '0') ?: '0';

                if (
                    $listStart !== '1'
                    && isset($Block)
                    && $Block instanceof Paragraph
                    && ! $Context->previousEmptyLines() > 0
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
     * @return self|null
     */
    public function advance(Context $Context)
    {
        if ($Context->previousEmptyLines() > 0 && \end($this->Lis)->isEmpty()) {
            return null;
        }

        $newlines = \str_repeat("\n", $Context->previousEmptyLines());

        $requiredIndent = $this->indent + \strlen($this->marker) + $this->afterMarkerSpaces;
        $isLoose = $this->isLoose;
        $indent = $Context->line()->indent();

        $Lis = $this->Lis;

        if ($Context->line()->indent() < $requiredIndent
            && ((
                $this->type === 'ol'
                && \preg_match('/^([0-9]++'.$this->markerTypeRegex.')([\t ]++.*|$)/', $Context->line()->text(), $matches)
            ) || (
                $this->type === 'ul'
                && \preg_match('/^('.$this->markerTypeRegex.')([\t ]++.*|$)/', $Context->line()->text(), $matches)
            ))
        ) {
            if ($Context->previousEmptyLines() > 0) {
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
        } elseif ($Context->line()->indent() < $requiredIndent && self::build($Context) !== null) {
            return null;
        }

        if ($Context->line()->indent() >= $requiredIndent) {
            if ($Context->previousEmptyLines() > 0) {
                $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingBlankLines($Context->previousEmptyLines());

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

        if (! $Context->previousEmptyLines() > 0) {
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
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                return new Element(
                    $this->type,
                    (
                        isset($this->listStart) && $this->listStart !== '1'
                        ? ['start' => $this->listStart]
                        : []
                    ),
                    \array_map(
                        /** @return Element */
                        function (Lines $Lines) use ($State) {
                            if ($this->isLoose && $Lines->trailingBlankLines() === 0) {
                                $Lines = $Lines->appendingBlankLines(1);
                            }

                            $Renderables = $State->applyTo((new Parsedown($State))->lines($Lines));

                            if (! $Lines->containsBlankLines()
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
                        $this->Lis
                    )
                );
            }
        );
    }
}
