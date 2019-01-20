<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Lines;
use Erusev\Parsedown\State;

final class TList implements ContinuableBlock
{
    use ContinuableBlockDefaultInterrupt, BlockAcquisition;

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
        $markerType,
        $markerTypeRegex
    ) {
        $this->Lis = $Lis;
        $this->listStart = $listStart;
        $this->isLoose = $isLoose;
        $this->indent = $indent;
        $this->type = $type;
        $this->marker = $marker;
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
            '/^('.$pattern.'([ ]++|$))(.*+)/',
            $Context->line()->text(),
            $matches
        )) {
            $contentIndent = \strlen($matches[2]);

            if ($contentIndent >= 5) {
                $contentIndent -= 1;
                $matches[1] = \substr($matches[1], 0, -$contentIndent);
                $matches[3] = \str_repeat(' ', $contentIndent) . $matches[3];
            } elseif ($contentIndent === 0) {
                $matches[1] .= ' ';
            }

            $text = $matches[3];

            $markerWithoutWhitespace = \rtrim($matches[1], " \t");
            $marker = $matches[1];
            $indent = $Context->line()->indent();
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($marker);
            $markerType = (
                $type === 'ul'
                ? $markerWithoutWhitespace
                : \substr($markerWithoutWhitespace, -1)
            );

            $markerTypeRegex = \preg_quote($markerType, '/');

            /** @var string|null */
            $listStart = null;

            if ($type === 'ol') {
                /** @psalm-suppress PossiblyFalseArgument */
                $listStart = \ltrim(\strstr($matches[1], $markerType, true), '0') ?: '0';

                if (
                    $listStart !== '1'
                    and isset($Block)
                    and $Block instanceof Paragraph
                    and ! $Context->previousEmptyLines() > 0
                ) {
                    return null;
                }
            }

            return new self(
                [!empty($text) ? Lines::fromTextLines($text, $indentOffset) : Lines::none()],
                $listStart,
                false,
                $indent,
                $type,
                $marker,
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
        if ($this->interrupted and \end($this->Lis)->isEmpty()) {
            return null;
        }

        $requiredIndent = $this->indent + \strlen($this->marker);
        $isLoose = $this->isLoose;
        $indent = $Context->line()->indent();

        $Lis = $this->Lis;

        if ($Context->line()->indent() < $requiredIndent
            && ((
                $this->type === 'ol'
                && \preg_match('/^([0-9]++'.$this->markerTypeRegex.')(?:[ ]++(.*)|$)/', $Context->line()->text(), $matches)
            ) || (
                $this->type === 'ul'
                && \preg_match('/^('.$this->markerTypeRegex.')(?:[ ]++(.*)|$)/', $Context->line()->text(), $matches)
            ))
        ) {
            if ($Context->previousEmptyLines() > 0) {
                $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingBlankLines(1);

                $isLoose = true;
            }

            $text = isset($matches[2]) ? $matches[2] : '';
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent() + \strlen($matches[1]);

            $Lis[] = Lines::fromTextLines($text, $indentOffset);

            return new self(
                $Lis,
                $this->listStart,
                $isLoose,
                $indent,
                $this->type,
                $this->marker,
                $this->markerType,
                $this->markerTypeRegex
            );
        } elseif ($Context->line()->indent() < $requiredIndent && self::build($Context) !== null) {
            return null;
        }

        if ($Context->line()->indent() >= $requiredIndent) {
            if ($Context->previousEmptyLines() > 0) {
                $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingBlankLines(1);

                $isLoose = true;
            }

            $text = $Context->line()->ltrimBodyUpto($requiredIndent);
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent();

            $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingTextLines($text, $indentOffset);

            return new self(
                $Lis,
                $this->listStart,
                $isLoose,
                $this->indent,
                $this->type,
                $this->marker,
                $this->markerType,
                $this->markerTypeRegex
            );
        }

        if (! $Context->previousEmptyLines() > 0) {
            $text = $Context->line()->ltrimBodyUpto($requiredIndent);
            $indentOffset = $Context->line()->indentOffset() + $Context->line()->indent();

            $Lis[\count($Lis) -1] = $Lis[\count($Lis) -1]->appendingTextLines($text, $indentOffset);

            return new self(
                $Lis,
                $this->listStart,
                $isLoose,
                $this->indent,
                $this->type,
                $this->marker,
                $this->markerType,
                $this->markerTypeRegex
            );
        }

        return null;
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
                    (
                        isset($this->listStart) && $this->listStart !== '1'
                        ? ['start' => $this->listStart]
                        : []
                    ),
                    \array_map(
                        /** @return Element */
                        function (Lines $Lines) use ($State, $Parsedown) {
                            if ($this->isLoose && $Lines->trailingBlankLines() === 0) {
                                $Lines = $Lines->appendingBlankLines(1);
                            }

                            $Renderables = $State->applyTo($Parsedown->lines($Lines));

                            if (! $Lines->containsBlankLines()
                                and isset($Renderables[0])
                                and $Renderables[0] instanceof Element
                                and $Renderables[0]->name() === 'p'
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
